<?php

namespace SanamPlugin\Sanamassets;

use Computer;
use CommonDBTM;
use Sanam\Asset\Asset;
use Monitor;
use NetworkEquipment;
use Peripheral;
use Phone;
use Printer;
use Session;

/**
 * Core-item hooks: identity on creation, immutability afterwards,
 * retirement on purge, and identifier duplicate detection.
 */
final class AssetHooks
{
    /** Native types that receive a SANAM identity, plus custom asset definitions. */
    private const NATIVE_TYPES = [Computer::class, Monitor::class, Peripheral::class, Phone::class, NetworkEquipment::class, Printer::class];

    /** Identifier fields checked for duplicates. */
    private const IDENTIFIER_FIELD = 'serial';

    /** @var array<int, array{asset_id: string, legacy: ?string}> keyed by spl_object_id */
    private static array $pending = [];

    /**
     * Placeholder values mean "no usable serial". Many devices share them, so they never count as duplicates.
     */
    public static function isRealIdentifier(string $value): bool
    {
        return $value !== '' && !in_array(strtoupper($value), ['NOT-VISIBLE', 'NONE', 'NA', 'N/A', 'UNKNOWN'], true);
    }

    /** Native classes searched by serial or name. */
    public static function searchableTypes(): array
    {
        return self::NATIVE_TYPES;
    }

    /**
     * Every class that receives a SANAM identity: native types plus each custom asset definition.
     * SANAM dispatches item hooks by exact class name, so the plugin registers one callback per class.
     *
     * @return list<string>
     */
    public static function managedClasses(): array
    {
        global $DB;

        $classes = self::NATIVE_TYPES;
        try {
            // Read the definitions directly: the definition manager is not bootstrapped yet when plugins initialise.
            foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_assets_assetdefinitions']) as $row) {
                $definition = new \Sanam\Asset\AssetDefinition();
                if ($definition->getFromDB((int) $row['id'])) {
                    $classes[] = $definition->getCustomObjectClassName();
                }
            }
        } catch (\Throwable) {
            // Definitions table not ready (fresh install): native types only.
        }
        return $classes;
    }

    public static function isManaged($item): bool
    {
        if ($item instanceof Asset) {
            return true;
        }
        foreach (self::NATIVE_TYPES as $class) {
            if ($item instanceof $class) {
                return true;
            }
        }
        return false;
    }

    /**
     * Allocate the identity before the row is inserted, so the number is in the INSERT itself.
     */
    public static function preItemAdd(CommonDBTM $item): void
    {
        if (!self::isManaged($item)) {
            return;
        }

        $typed = trim((string) ($item->input['otherserial'] ?? ''));
        $asset_id = AssetId::allocate();
        $item->input['otherserial'] = $asset_id;

        self::$pending[spl_object_id($item)] = [
            'asset_id' => $asset_id,
            'legacy'   => $typed !== '' ? $typed : null,
        ];

        $serial = trim((string) ($item->input[self::IDENTIFIER_FIELD] ?? ''));
        if (self::isRealIdentifier($serial) && empty($item->input['_sanam_allow_duplicate'])) {
            $matches = self::findSerialMatches($serial, 0, '');
            if ($matches !== []) {
                $names = implode(', ', array_map(static fn($m) => $m['label'], $matches));
                Session::addMessageAfterRedirect(
                    sprintf(
                        __('Serial "%s" already exists on: %s. Open the existing record instead of creating a second one.', 'sanamassets'),
                        $serial,
                        $names,
                    ),
                    false,
                    ERROR,
                );
                $item->input = false;
            }
        }
    }

    public static function itemAdd(CommonDBTM $item): void
    {
        $key = spl_object_id($item);
        if (!isset(self::$pending[$key])) {
            return;
        }

        $data = self::$pending[$key];
        unset(self::$pending[$key]);

        AssetId::register($data['asset_id'], $item::class, (int) $item->getID(), $data['legacy']);
        Label::record($data['asset_id'], 'generated', 'Identity created');
    }

    /**
     * Refuse any change to an existing SANAM identity. Also records identifier conflicts.
     */
    public static function preItemUpdate(CommonDBTM $item): void
    {
        if (!self::isManaged($item)) {
            return;
        }

        $current = (string) ($item->fields['otherserial'] ?? '');
        if (AssetId::isValid($current) && array_key_exists('otherserial', $item->input ?? [])) {
            if ((string) $item->input['otherserial'] !== $current) {
                $item->input['otherserial'] = $current;
                Session::addMessageAfterRedirect(
                    sprintf(__('The asset identity %s cannot be changed.', 'sanamassets'), $current),
                    false,
                    WARNING,
                );
            }
        }

        if (array_key_exists(self::IDENTIFIER_FIELD, $item->input ?? [])) {
            $serial = trim((string) $item->input[self::IDENTIFIER_FIELD]);
            if (self::isRealIdentifier($serial) && $serial !== (string) ($item->fields[self::IDENTIFIER_FIELD] ?? '')) {
                foreach (self::findSerialMatches($serial, (int) $item->getID(), $item->getTable()) as $match) {
                    Conflict::open($item::class, (int) $item->getID(), $match['itemtype'], $match['items_id'], 'serial', $serial);
                }
            }
        }
    }

    public static function itemPurge(CommonDBTM $item): void
    {
        if (self::isManaged($item)) {
            AssetId::retire($item::class, (int) $item->getID());
        }
    }

    /**
     * Find other managed items that carry the same identifier value.
     *
     * @return list<array{itemtype: string, items_id: int, label: string}>
     */
    public static function findSerialMatches(string $serial, int $exclude_id, string $exclude_table): array
    {
        global $DB;

        // Custom assets all live in one table, so they are compared by table name.
        $tables = [];
        foreach (self::NATIVE_TYPES as $class) {
            $tables[(new $class())->getTable()] = $class;
        }
        $tables['sanam_assets_assets'] = Asset::class;

        $matches = [];
        foreach ($tables as $table => $class) {
            $where = [self::IDENTIFIER_FIELD => $serial, 'is_deleted' => 0];
            if ($table === $exclude_table && $exclude_id > 0) {
                $where[] = ['NOT' => ['id' => $exclude_id]];
            }
            foreach ($DB->request(['SELECT' => ['id', 'name'], 'FROM' => $table, 'WHERE' => $where]) as $row) {
                $matches[] = [
                    'itemtype' => $class,
                    'items_id' => (int) $row['id'],
                    'label'    => $class . ' #' . $row['id'] . ' ' . $row['name'],
                ];
            }
        }
        return $matches;
    }
}
