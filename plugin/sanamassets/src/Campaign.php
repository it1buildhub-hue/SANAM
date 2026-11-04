<?php

namespace SanamPlugin\Sanamassets;

use RuntimeException;
use Session;

/**
 * Physical audit campaigns.
 *
 * Lifecycle: draft -> active -> review -> closed (-> active again only by a logged reopen).
 *
 * Rules enforced here, not in the UI:
 * - Scope is frozen into an expected snapshot when the campaign starts.
 * - One verification per item per campaign (unique key). A second scan is reported as a duplicate.
 * - A verification never changes location or custody. Differences become discrepancies.
 * - "Not found" becomes a discrepancy to investigate, never an automatic "lost" status.
 * - A campaign cannot close while an area is unfinished or a discrepancy is unreviewed.
 * - Closed campaigns reject new verifications.
 */
final class Campaign
{
    public const KINDS    = ['baseline', 'periodic', 'spot'];
    public const STATUSES = ['draft', 'active', 'review', 'closed'];

    public static function create(string $name, int $entities_id, string $kind): int
    {
        global $DB;

        if (!in_array($kind, self::KINDS, true)) {
            throw new RuntimeException('Invalid campaign kind.');
        }
        $DB->insert('sanam_plugin_sanamassets_campaigns', [
            'name'           => $name,
            'entities_id'    => $entities_id,
            'kind'           => $kind,
            'status'         => 'draft',
            'users_id_owner' => (int) Session::getLoginUserID(false),
            'date_creation'  => date('Y-m-d H:i:s'),
            'date_mod'       => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $DB->insertId();
        self::log($id, 'created', null);
        return $id;
    }

    public static function get(int $id): ?array
    {
        global $DB;

        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_campaigns', 'WHERE' => ['id' => $id], 'LIMIT' => 1]) as $row) {
            return $row;
        }
        return null;
    }

    public static function addArea(int $campaign_id, string $name, int $locations_id): int
    {
        global $DB;

        self::assertStatus($campaign_id, ['draft', 'active']);
        $DB->insert('sanam_plugin_sanamassets_campaignareas', [
            'campaigns_id' => $campaign_id,
            'locations_id' => $locations_id,
            'name'         => $name,
            'status'       => 'not_started',
        ]);
        return (int) $DB->insertId();
    }

    /** @return list<array<string, mixed>> */
    public static function areas(int $campaign_id): array
    {
        global $DB;

        $rows = [];
        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_campaignareas', 'WHERE' => ['campaigns_id' => $campaign_id], 'ORDER' => ['name ASC']]) as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Freeze the expected records for this campaign. Items added later are unexpected.
     */
    public static function start(int $campaign_id): int
    {
        global $DB;

        $campaign = self::get($campaign_id) ?? throw new RuntimeException('Campaign not found.');
        self::assertStatus($campaign_id, ['draft']);
        if (self::areas($campaign_id) === []) {
            throw new RuntimeException('Add at least one area before starting the campaign.');
        }

        $scope = getSonsOf('sanam_entities', (int) $campaign['entities_id']);
        $count = 0;

        $DB->beginTransaction();
        try {
            foreach (Items::activeIdentities() as $identity) {
                $item = $identity['item'];
                if (!in_array((int) $item->fields['entities_id'], $scope, true)) {
                    continue;
                }
                $DB->insert('sanam_plugin_sanamassets_expected', [
                    'campaigns_id' => $campaign_id,
                    'itemtype'     => $identity['itemtype'],
                    'items_id'     => $identity['items_id'],
                    'asset_id'     => $identity['asset_id'],
                    'locations_id' => (int) ($item->fields['locations_id'] ?? 0),
                    'users_id'     => (int) ($item->fields['users_id'] ?? 0),
                    'states_id'    => (int) ($item->fields['states_id'] ?? 0),
                ]);
                $count++;
            }
            $DB->update(
                'sanam_plugin_sanamassets_campaigns',
                ['status' => 'active', 'date_start' => date('Y-m-d H:i:s'), 'date_mod' => date('Y-m-d H:i:s')],
                ['id' => $campaign_id],
            );
            $DB->commit();
        } catch (\Throwable $e) {
            $DB->rollBack();
            throw $e;
        }

        self::log($campaign_id, 'started', sprintf('%d expected records', $count));
        return $count;
    }

    /**
     * Record one physical verification.
     *
     * @return array{result: string, message: string}  result: verified|duplicate|unknown|unexpected
     */
    public static function verify(
        int $campaign_id,
        string $asset_id,
        int $area_id,
        string $method,
        int $observed_location,
        int $observed_user,
        string $state_observed,
        string $comment,
    ): array {
        global $DB;

        $campaign = self::get($campaign_id) ?? throw new RuntimeException('Campaign not found.');
        if ($campaign['status'] !== 'active') {
            throw new RuntimeException('This campaign is not open for verification.');
        }

        $identity = Items::byAssetId($asset_id);
        if ($identity === null) {
            Scan::record($campaign_id, $asset_id, $method, 'not_found');
            return ['result' => 'unknown', 'message' => 'Unknown asset identity: ' . $asset_id];
        }

        $area = self::area($campaign_id, $area_id);
        $user = (int) Session::getLoginUserID(false);

        if (self::isVerified($campaign_id, $identity['itemtype'], $identity['items_id'])) {
            Scan::record($campaign_id, $asset_id, $method, 'duplicate');
            return ['result' => 'duplicate', 'message' => $asset_id . ' was already verified in this campaign.'];
        }

        $expected = self::expectedRow($campaign_id, $identity['itemtype'], $identity['items_id']);

        $DB->beginTransaction();
        try {
            $DB->insert('sanam_plugin_sanamassets_verifications', [
                'campaigns_id'     => $campaign_id,
                'itemtype'         => $identity['itemtype'],
                'items_id'         => $identity['items_id'],
                'asset_id'         => $asset_id,
                'areas_id'         => $area['id'],
                'users_id'         => $user,
                'method'           => $method,
                'locations_id'     => $observed_location ?: $area['locations_id'],
                'users_id_found'   => $observed_user ?: null,
                'state_observed'   => $state_observed !== '' ? $state_observed : null,
                'comment'          => $comment !== '' ? $comment : null,
                'date_verified'    => date('Y-m-d H:i:s'),
            ]);
            if ((int) $DB->affectedRows() !== 1) {
                throw new RuntimeException('Verification was not stored.');
            }

            if ($area['status'] === 'not_started') {
                $DB->update('sanam_plugin_sanamassets_campaignareas', ['status' => 'in_progress', 'users_id_auditor' => $user], ['id' => $area['id']]);
            }
            $DB->commit();
        } catch (\Throwable $e) {
            $DB->rollBack();
            // Another auditor may have won the unique-key race for the same item.
            if (self::isVerified($campaign_id, $identity['itemtype'], $identity['items_id'])) {
                Scan::record($campaign_id, $asset_id, $method, 'duplicate');
                return ['result' => 'duplicate', 'message' => $asset_id . ' was verified by another auditor.'];
            }
            throw $e;
        }

        Scan::record($campaign_id, $asset_id, $method, 'found');

        if ($expected === null) {
            Discrepancy::open($campaign_id, 'unexpected', $identity['itemtype'], $identity['items_id'], $asset_id, 'Found in campaign area ' . $area['name'] . ', not in expected snapshot');
            return ['result' => 'unexpected', 'message' => $asset_id . ' verified, flagged as unexpected.'];
        }

        if ($observed_location !== 0 && (int) $expected['locations_id'] !== $observed_location) {
            Discrepancy::open($campaign_id, 'location', $identity['itemtype'], $identity['items_id'], $asset_id, 'Recorded location differs from the location observed during verification');
        }
        if ($observed_user !== 0 && (int) $expected['users_id'] !== $observed_user) {
            Discrepancy::open($campaign_id, 'custody', $identity['itemtype'], $identity['items_id'], $asset_id, 'Recorded custodian differs from the custodian confirmed during verification');
        }

        return ['result' => 'verified', 'message' => $asset_id . ' verified.'];
    }

    /** Area status changes: done, or inaccessible with a reason (ceiling cameras, locked rooms). */
    public static function setAreaStatus(int $campaign_id, int $area_id, string $status, ?string $reason = null): void
    {
        global $DB;

        self::assertStatus($campaign_id, ['active', 'review']);
        if (!in_array($status, ['in_progress', 'done', 'inaccessible'], true)) {
            throw new RuntimeException('Invalid area status.');
        }
        if ($status === 'inaccessible' && trim((string) $reason) === '') {
            throw new RuntimeException('Give a reason when an area cannot be inspected.');
        }
        self::area($campaign_id, $area_id);

        $DB->update(
            'sanam_plugin_sanamassets_campaignareas',
            [
                'status'               => $status,
                'inaccessible_reason'  => $status === 'inaccessible' ? $reason : null,
                'date_done'            => in_array($status, ['done', 'inaccessible'], true) ? date('Y-m-d H:i:s') : null,
            ],
            ['id' => $area_id, 'campaigns_id' => $campaign_id],
        );
    }

    /**
     * Summary used by the coverage screen and the close check.
     *
     * @return array{expected: int, verified: int, not_inspected_areas: list<string>, open_discrepancies: int}
     */
    public static function coverage(int $campaign_id): array
    {
        global $DB;

        $expected = (int) self::count('sanam_plugin_sanamassets_expected', ['campaigns_id' => $campaign_id]);
        $verified = (int) self::count('sanam_plugin_sanamassets_verifications', ['campaigns_id' => $campaign_id]);

        $not_inspected = [];
        foreach (self::areas($campaign_id) as $area) {
            if (!in_array($area['status'], ['done', 'inaccessible'], true)) {
                $not_inspected[] = $area['name'];
            }
        }

        return [
            'expected'            => $expected,
            'verified'            => $verified,
            'not_inspected_areas' => $not_inspected,
            'open_discrepancies'  => (int) self::count('sanam_plugin_sanamassets_discrepancies', ['campaigns_id' => $campaign_id, 'status' => 'open']),
        ];
    }

    /**
     * Move to review: every expected item that was not verified becomes a "not found" discrepancy.
     */
    public static function moveToReview(int $campaign_id): int
    {
        global $DB;

        self::assertStatus($campaign_id, ['active']);
        $found = 0;

        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_expected', 'WHERE' => ['campaigns_id' => $campaign_id]]) as $row) {
            if (self::isVerified($campaign_id, $row['itemtype'], (int) $row['items_id'])) {
                continue;
            }
            // A reopened campaign must not repeat findings that were already recorded.
            if (Discrepancy::existsFor($campaign_id, 'not_found', $row['itemtype'], (int) $row['items_id'])) {
                continue;
            }
            Discrepancy::open($campaign_id, 'not_found', $row['itemtype'], (int) $row['items_id'], $row['asset_id'], 'Not verified during the campaign. Investigate before marking as lost.');
            $found++;
        }

        $DB->update('sanam_plugin_sanamassets_campaigns', ['status' => 'review', 'date_mod' => date('Y-m-d H:i:s')], ['id' => $campaign_id]);
        self::log($campaign_id, 'review', sprintf('%d not found', $found));
        return $found;
    }

    /**
     * Close and lock. Refuses while coverage or review is incomplete.
     */
    public static function close(int $campaign_id): void
    {
        global $DB;

        self::assertStatus($campaign_id, ['review']);

        $coverage = self::coverage($campaign_id);
        if ($coverage['not_inspected_areas'] !== []) {
            throw new RuntimeException('Areas not finished: ' . implode(', ', $coverage['not_inspected_areas']));
        }
        if ($coverage['open_discrepancies'] > 0) {
            throw new RuntimeException($coverage['open_discrepancies'] . ' discrepancies still need a decision.');
        }

        $DB->update(
            'sanam_plugin_sanamassets_campaigns',
            ['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s'), 'closed_by' => (int) Session::getLoginUserID(false), 'date_mod' => date('Y-m-d H:i:s')],
            ['id' => $campaign_id],
        );
        self::log($campaign_id, 'closed', null);
    }

    /** Reopen a closed campaign. Only the reason is logged; prior verifications stay untouched. */
    public static function reopen(int $campaign_id, string $reason): void
    {
        global $DB;

        self::assertStatus($campaign_id, ['closed']);
        if (trim($reason) === '') {
            throw new RuntimeException('A reason is required to reopen a campaign.');
        }

        $campaign = self::get($campaign_id);
        $DB->update(
            'sanam_plugin_sanamassets_campaigns',
            ['status' => 'active', 'reopen_count' => ((int) $campaign['reopen_count']) + 1, 'date_mod' => date('Y-m-d H:i:s')],
            ['id' => $campaign_id],
        );
        self::log($campaign_id, 'reopened', $reason);
    }

    public static function log(int $campaign_id, string $action, ?string $reason): void
    {
        global $DB;

        $DB->insert('sanam_plugin_sanamassets_campaignlogs', [
            'campaigns_id' => $campaign_id,
            'action'       => $action,
            'reason'       => $reason,
            'users_id'     => (int) Session::getLoginUserID(false),
            'date_action'  => date('Y-m-d H:i:s'),
        ]);
    }

    public static function isVerified(int $campaign_id, string $itemtype, int $items_id): bool
    {
        return self::count('sanam_plugin_sanamassets_verifications', [
            'campaigns_id' => $campaign_id,
            'itemtype'     => $itemtype,
            'items_id'     => $items_id,
        ]) > 0;
    }

    private static function area(int $campaign_id, int $area_id): array
    {
        global $DB;

        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_campaignareas', 'WHERE' => ['id' => $area_id, 'campaigns_id' => $campaign_id], 'LIMIT' => 1]) as $row) {
            if ($row['status'] === 'inaccessible') {
                throw new RuntimeException('This area is marked inaccessible. Reopen it before verifying items there.');
            }
            return $row;
        }
        throw new RuntimeException('Area does not belong to this campaign.');
    }

    private static function expectedRow(int $campaign_id, string $itemtype, int $items_id): ?array
    {
        global $DB;

        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_expected', 'WHERE' => ['campaigns_id' => $campaign_id, 'itemtype' => $itemtype, 'items_id' => $items_id], 'LIMIT' => 1]) as $row) {
            return $row;
        }
        return null;
    }

    private static function assertStatus(int $campaign_id, array $allowed): void
    {
        $campaign = self::get($campaign_id) ?? throw new RuntimeException('Campaign not found.');
        if (!in_array($campaign['status'], $allowed, true)) {
            throw new RuntimeException(sprintf('Campaign is "%s"; this action needs "%s".', $campaign['status'], implode(' or ', $allowed)));
        }
    }

    private static function count(string $table, array $where): int
    {
        global $DB;

        $iterator = $DB->request(['COUNT' => 'cnt', 'FROM' => $table, 'WHERE' => $where]);
        foreach ($iterator as $row) {
            return (int) $row['cnt'];
        }
        return 0;
    }
}
