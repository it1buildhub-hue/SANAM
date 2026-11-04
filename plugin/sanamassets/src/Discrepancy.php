<?php

namespace SanamPlugin\Sanamassets;

use InvalidArgumentException;
use Session;

/**
 * Findings from an audit. A finding is recorded, reviewed, and only then closed.
 * Approving a finding never edits the asset. Corrections are separate, explicit changes.
 */
final class Discrepancy
{
    public const TYPES = ['unexpected', 'location', 'custody', 'not_found', 'damage', 'label', 'conflict'];

    public static function open(
        int $campaign_id,
        string $type,
        string $itemtype,
        int $items_id,
        ?string $asset_id,
        string $details,
    ): void {
        global $DB;

        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Unknown discrepancy type: ' . $type);
        }

        $DB->insert('sanam_plugin_sanamassets_discrepancies', [
            'campaigns_id'  => $campaign_id,
            'type'          => $type,
            'itemtype'      => $itemtype,
            'items_id'      => $items_id,
            'asset_id'      => $asset_id,
            'details'       => $details,
            'status'        => 'open',
            'date_creation' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function existsFor(int $campaign_id, string $type, string $itemtype, int $items_id): bool
    {
        global $DB;

        foreach ($DB->request(['COUNT' => 'c', 'FROM' => 'sanam_plugin_sanamassets_discrepancies', 'WHERE' => [
            'campaigns_id' => $campaign_id,
            'type'         => $type,
            'itemtype'     => $itemtype,
            'items_id'     => $items_id,
        ]]) as $row) {
            return (int) $row['c'] > 0;
        }
        return false;
    }

    /** @return list<array<string, mixed>> */
    public static function forCampaign(int $campaign_id): array
    {
        global $DB;

        $rows = [];
        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_discrepancies', 'WHERE' => ['campaigns_id' => $campaign_id], 'ORDER' => ['id ASC']]) as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    public static function review(int $id, string $decision): void
    {
        global $DB;

        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Invalid decision: ' . $decision);
        }

        $DB->update(
            'sanam_plugin_sanamassets_discrepancies',
            [
                'status'            => $decision,
                'users_id_reviewer' => (int) Session::getLoginUserID(false),
                'date_reviewed'     => date('Y-m-d H:i:s'),
            ],
            ['id' => $id, 'status' => 'open'],
        );
    }
}
