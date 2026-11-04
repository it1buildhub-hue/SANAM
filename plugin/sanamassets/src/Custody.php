<?php

namespace SanamPlugin\Sanamassets;

use RuntimeException;
use Session;

/**
 * Custody: who holds which asset.
 *
 * Rules:
 * - An item has at most one active permanent or temporary holder.
 * - Shared assets can have several active shared assignments, each with a responsible group or person.
 * - Temporary assignments need an expected return date.
 * - Issuing or returning updates the native "users_id" of the item, so SANAM reports stay correct.
 * - A verification in an audit never changes custody. Only these actions do.
 */
final class Custody
{
    public const KINDS = ['permanent', 'temporary', 'shared'];
    public const ACKS  = ['signed_paper', 'screen', 'account', 'none'];

    /**
     * @param array{kind: string, users_id?: int, groups_id?: int, expected_return?: string,
     *              condition?: string, accessories?: string, ack?: string, note?: string} $data
     */
    public static function issue(string $itemtype, int $items_id, array $data): int
    {
        global $DB;

        $kind = $data['kind'] ?? '';
        if (!in_array($kind, self::KINDS, true)) {
            throw new RuntimeException('Choose permanent, temporary or shared.');
        }

        $item = Items::load($itemtype, $items_id) ?? throw new RuntimeException('Item not found.');
        $asset_id = (string) $item->fields['otherserial'];
        $users_id = (int) ($data['users_id'] ?? 0);
        $groups_id = (int) ($data['groups_id'] ?? 0);

        if ($kind !== 'shared' && $users_id === 0) {
            throw new RuntimeException('Choose the person who receives this asset.');
        }
        if ($kind === 'shared' && $users_id === 0 && $groups_id === 0) {
            throw new RuntimeException('A shared asset needs a responsible person or group.');
        }
        if ($users_id > 0 && !self::userIsActive($users_id)) {
            throw new RuntimeException('That user is not active.');
        }

        $expected = $data['expected_return'] ?? null;
        if ($kind === 'temporary') {
            if (empty($expected) || $expected < date('Y-m-d')) {
                throw new RuntimeException('A temporary assignment needs a return date in the future.');
            }
        } else {
            $expected = null;
        }

        $ack = $data['ack'] ?? 'none';
        if (!in_array($ack, self::ACKS, true)) {
            $ack = 'none';
        }

        $DB->beginTransaction();
        try {
            if ($kind !== 'shared') {
                // Lock the item's holder rows so two issues cannot both succeed.
                $DB->doQuery(sprintf(
                    "SELECT id FROM sanam_plugin_sanamassets_assignments WHERE itemtype = '%s' AND items_id = %d AND status = 'active' AND kind <> 'shared' FOR UPDATE",
                    $DB->escape($itemtype),
                    $items_id,
                ));
                if (self::activeHolderCount($itemtype, $items_id) > 0) {
                    throw new RuntimeException('This asset already has an active holder. Return it first or transfer it.');
                }
            }

            $DB->insert('sanam_plugin_sanamassets_assignments', [
                'itemtype'             => $itemtype,
                'items_id'             => $items_id,
                'asset_id'             => $asset_id,
                'kind'                 => $kind,
                'users_id'             => $users_id ?: null,
                'groups_id'            => $groups_id ?: null,
                'date_start'           => date('Y-m-d H:i:s'),
                'date_expected_return' => $expected,
                'condition_issue'      => $data['condition'] ?? null,
                'accessories'          => $data['accessories'] ?? null,
                'ack_method'           => $ack,
                'note'                 => $data['note'] ?? null,
                'users_id_issuer'      => (int) Session::getLoginUserID(false),
                'status'               => 'active',
            ]);
            $assignment_id = (int) $DB->insertId();

            if ($kind !== 'shared' && $users_id > 0) {
                $item->update(['id' => $items_id, 'users_id' => $users_id]);
            }
            $DB->commit();
        } catch (\Throwable $e) {
            $DB->rollBack();
            throw $e;
        }

        return $assignment_id;
    }

    /** Return an assignment. Native custodian is cleared only if nobody else holds the item. */
    public static function returnAssignment(int $assignment_id, string $condition, string $accessories, string $note): void
    {
        global $DB;

        $assignment = self::find($assignment_id) ?? throw new RuntimeException('Assignment not found.');
        if ($assignment['status'] !== 'active') {
            throw new RuntimeException('This assignment is already returned.');
        }

        $DB->beginTransaction();
        try {
            $DB->update(
                'sanam_plugin_sanamassets_assignments',
                [
                    'status'           => 'returned',
                    'date_returned'    => date('Y-m-d H:i:s'),
                    'condition_return' => $condition !== '' ? $condition : null,
                    'accessories'      => $accessories !== '' ? $accessories : $assignment['accessories'],
                    'note'             => trim(($assignment['note'] ?? '') . "\n" . $note),
                ],
                ['id' => $assignment_id],
            );

            if ($assignment['kind'] !== 'shared' && self::activeHolderCount($assignment['itemtype'], (int) $assignment['items_id']) === 0) {
                $item = Items::load($assignment['itemtype'], (int) $assignment['items_id']);
                if ($item !== null) {
                    $item->update(['id' => (int) $assignment['items_id'], 'users_id' => 0]);
                }
            }
            $DB->commit();
        } catch (\Throwable $e) {
            $DB->rollBack();
            throw $e;
        }
    }

    /** Employee-to-employee transfer: return and issue as one unit. */
    public static function transfer(int $assignment_id, int $new_users_id, string $condition, string $note): int
    {
        global $DB;

        $assignment = self::find($assignment_id) ?? throw new RuntimeException('Assignment not found.');
        if ($assignment['status'] !== 'active') {
            throw new RuntimeException('Only an active assignment can be transferred.');
        }

        $DB->beginTransaction();
        try {
            self::returnAssignment($assignment_id, $condition, '', 'Transferred. ' . $note);
            $new_id = self::issue($assignment['itemtype'], (int) $assignment['items_id'], [
                'kind'           => $assignment['kind'] === 'shared' ? 'shared' : $assignment['kind'],
                'users_id'       => $new_users_id,
                'expected_return' => $assignment['date_expected_return'],
                'condition'      => $condition,
                'accessories'    => $assignment['accessories'] ?? '',
                'note'           => 'Transferred from assignment ' . $assignment_id,
            ]);
            $DB->commit();
        } catch (\Throwable $e) {
            $DB->rollBack();
            throw $e;
        }

        return $new_id;
    }

    /**
     * Data for the printable handover/return sheet (English and Arabic labels are added by the template).
     *
     * @return array{person: string, date: string, items: list<array<string, string>>}
     */
    public static function handoverSheet(int $users_id): array
    {
        global $DB;

        $person = new \User();
        $person->getFromDB($users_id);

        $items = [];
        foreach (self::activeFor($users_id) as $a) {
            $item = Items::load($a['itemtype'], (int) $a['items_id']);
            $items[] = [
                'asset_id'    => (string) $a['asset_id'],
                'name'        => $item ? (string) $item->fields['name'] : '(removed)',
                'kind'        => $a['kind'],
                'since'       => (string) $a['date_start'],
                'expected'    => (string) ($a['date_expected_return'] ?? ''),
                'accessories' => (string) ($a['accessories'] ?? ''),
            ];
        }

        return [
            'person' => (string) $person->fields['name'],
            'date'   => date('Y-m-d'),
            'items'  => $items,
        ];
    }

    /** Outstanding items for an employee: active assignments and lines still in their name. */
    public static function outstanding(int $users_id): array
    {
        global $DB;

        $lines = [];
        foreach ($DB->request(['SELECT' => ['id', 'name'], 'FROM' => 'sanam_lines', 'WHERE' => ['users_id' => $users_id, 'is_deleted' => 0]]) as $row) {
            $lines[] = ['line_id' => (int) $row['id'], 'name' => (string) $row['name']];
        }

        return [
            'assignments' => self::activeFor($users_id),
            'lines'       => $lines,
        ];
    }

    /**
     * Clearance can be granted only when nothing is outstanding.
     */
    public static function clear(int $users_id, string $note): void
    {
        global $DB;

        $open = self::outstanding($users_id);
        if ($open['assignments'] !== [] || $open['lines'] !== []) {
            throw new RuntimeException(sprintf(
                '%d assets and %d lines are still outstanding.',
                count($open['assignments']),
                count($open['lines']),
            ));
        }

        $DB->insert('sanam_plugin_sanamassets_clearances', [
            'users_id'          => $users_id,
            'status'            => 'cleared',
            'users_id_approver' => (int) Session::getLoginUserID(false),
            'note'              => $note !== '' ? $note : null,
            'date_creation'     => date('Y-m-d H:i:s'),
            'date_cleared'      => date('Y-m-d H:i:s'),
        ]);
    }

    /** Temporary assignments past their expected return date. */
    public static function overdue(): array
    {
        global $DB;

        $rows = [];
        foreach ($DB->request([
            'FROM'  => 'sanam_plugin_sanamassets_assignments',
            'WHERE' => ['status' => 'active', 'kind' => 'temporary', ['date_expected_return' => ['<', date('Y-m-d')]]],
        ]) as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function activeFor(int $users_id): array
    {
        global $DB;

        $rows = [];
        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_assignments', 'WHERE' => ['users_id' => $users_id, 'status' => 'active'], 'ORDER' => ['date_start ASC']]) as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    private static function find(int $id): ?array
    {
        global $DB;

        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_assignments', 'WHERE' => ['id' => $id], 'LIMIT' => 1]) as $row) {
            return $row;
        }
        return null;
    }

    private static function activeHolderCount(string $itemtype, int $items_id): int
    {
        global $DB;

        foreach ($DB->request([
            'COUNT' => 'cnt',
            'FROM'  => 'sanam_plugin_sanamassets_assignments',
            'WHERE' => ['itemtype' => $itemtype, 'items_id' => $items_id, 'status' => 'active', 'kind' => ['<>', 'shared']],
        ]) as $row) {
            return (int) $row['cnt'];
        }
        return 0;
    }

    private static function userIsActive(int $users_id): bool
    {
        $user = new \User();
        return $user->getFromDB($users_id) && (int) $user->fields['is_active'] === 1 && (int) $user->fields['is_deleted'] === 0;
    }
}
