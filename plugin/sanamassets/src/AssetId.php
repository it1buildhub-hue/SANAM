<?php

namespace SanamPlugin\Sanamassets;

/**
 * Global, immutable asset identity: SNM-000001, SNM-000002, ...
 *
 * - Allocated from a single locked counter, so two requests never get the same number.
 * - Never reused: a purged item's identity is retired, not released.
 * - Stored in the native "otherserial" field so every SANAM report shows it.
 */
final class AssetId
{
    public const PREFIX  = 'SNM-';
    public const PATTERN = '/^SNM-\d{6,}$/';

    public static function isValid(?string $value): bool
    {
        return $value !== null && preg_match(self::PATTERN, $value) === 1;
    }

    public static function format(int $number): string
    {
        return self::PREFIX . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Reserve the next identity. Runs in its own transaction with a row lock.
     */
    public static function allocate(): string
    {
        global $DB;

        $DB->beginTransaction();
        try {
            $res = $DB->doQuery(
                'SELECT next_value FROM sanam_plugin_sanamassets_sequences WHERE id = 1 FOR UPDATE'
            );
            $row = $DB->fetchAssoc($res);
            $number = (int) $row['next_value'];

            $DB->doQuery(
                'UPDATE sanam_plugin_sanamassets_sequences SET next_value = ' . ($number + 1) . ' WHERE id = 1'
            );
            $DB->commit();
        } catch (\Throwable $e) {
            $DB->rollBack();
            throw $e;
        }

        return self::format($number);
    }

    /** @return array{asset_id: string, itemtype: string, items_id: int, status: string}|null */
    public static function find(string $asset_id): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => 'sanam_plugin_sanamassets_assetids',
            'WHERE' => ['asset_id' => $asset_id],
            'LIMIT' => 1,
        ]);

        foreach ($iterator as $row) {
            return $row;
        }
        return null;
    }

    public static function register(string $asset_id, string $itemtype, int $items_id, ?string $legacy_ref): void
    {
        global $DB;

        $DB->insert('sanam_plugin_sanamassets_assetids', [
            'asset_id'    => $asset_id,
            'itemtype'    => $itemtype,
            'items_id'    => $items_id,
            'status'      => 'active',
            'legacy_ref'  => $legacy_ref,
            'date_creation' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Called when an item is purged. The number stays retired forever. */
    public static function retire(string $itemtype, int $items_id): void
    {
        global $DB;

        $DB->update(
            'sanam_plugin_sanamassets_assetids',
            ['status' => 'retired', 'date_retired' => date('Y-m-d H:i:s')],
            ['itemtype' => $itemtype, 'items_id' => $items_id, 'status' => 'active'],
        );
    }
}
