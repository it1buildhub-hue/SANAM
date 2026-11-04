<?php

namespace SanamPlugin\Sanamassets;

use Session;

/**
 * Identifier conflicts: two items that carry the same serial/IMEI/etc.
 *
 * Conflicts are flagged for review. They are never merged automatically.
 */
final class Conflict
{
    public static function open(
        string $itemtype,
        int $items_id,
        string $other_itemtype,
        int $other_items_id,
        string $field,
        string $value,
    ): void {
        global $DB;

        $DB->insert('sanam_plugin_sanamassets_conflicts', [
            'itemtype'        => $itemtype,
            'items_id'        => $items_id,
            'other_itemtype'  => $other_itemtype,
            'other_items_id'  => $other_items_id,
            'field'           => $field,
            'value'           => $value,
            'status'          => 'open',
            'users_id'        => (int) Session::getLoginUserID(false),
            'date_creation'   => date('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function listOpen(): array
    {
        global $DB;

        $rows = [];
        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_conflicts', 'WHERE' => ['status' => 'open'], 'ORDER' => ['id ASC']]) as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /** Record a reviewer decision. "accepted" keeps both items as they are. */
    public static function review(int $id, string $decision): void
    {
        global $DB;

        if (!in_array($decision, ['accepted', 'rejected'], true)) {
            throw new \InvalidArgumentException('Invalid decision: ' . $decision);
        }

        $DB->update(
            'sanam_plugin_sanamassets_conflicts',
            [
                'status'            => $decision,
                'users_id_reviewer' => (int) Session::getLoginUserID(false),
                'date_reviewed'     => date('Y-m-d H:i:s'),
            ],
            ['id' => $id, 'status' => 'open'],
        );
    }
}
