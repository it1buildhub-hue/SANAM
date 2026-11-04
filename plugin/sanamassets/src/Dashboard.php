<?php

namespace SanamPlugin\Sanamassets;

/**
 * Actionable lists for the daily workspace. Each entry is a count plus the list behind it,
 * so a manager can act on the list rather than read a number.
 */
final class Dashboard
{
    /** @return array<string, array{label: string, count: int, items: list<array<string, string>>}> */
    public static function compute(): array
    {
        global $DB;

        $missing_location = [];
        $missing_custodian = [];
        foreach (Items::activeIdentities() as $identity) {
            $f = $identity['item']->fields;
            $row = self::row($identity);
            if ((int) ($f['locations_id'] ?? 0) === 0) {
                $missing_location[] = $row;
            }
            if ((int) ($f['users_id'] ?? 0) === 0 && (int) ($f['groups_id'] ?? 0) === 0) {
                $missing_custodian[] = $row;
            }
        }

        $overdue = [];
        foreach (Custody::overdue() as $a) {
            $overdue[] = ['asset_id' => (string) $a['asset_id'], 'label' => 'Due ' . $a['date_expected_return'], 'link' => ''];
        }

        $repair_states = self::repairStateIds();
        $repairs = [];
        foreach (Items::activeIdentities() as $identity) {
            if (in_array((int) $identity['item']->fields['states_id'], $repair_states, true)) {
                $repairs[] = self::row($identity);
            }
        }

        $unverified = [];
        $last = self::lastClosedCampaignId();
        if ($last !== null) {
            foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_expected', 'WHERE' => ['campaigns_id' => $last]]) as $e) {
                if (!Campaign::isVerified($last, $e['itemtype'], (int) $e['items_id'])) {
                    $unverified[] = ['asset_id' => (string) $e['asset_id'], 'label' => $e['itemtype'] . ' #' . $e['items_id'], 'link' => ''];
                }
            }
        }

        $warranty = [];
        $limit = date('Y-m-d', strtotime('+60 days'));
        foreach ($DB->request(['SELECT' => ['itemtype', 'items_id', 'warranty_date', 'warranty_duration'], 'FROM' => 'sanam_infocoms', 'WHERE' => ['warranty_duration' => ['>', 0]]]) as $i) {
            $end = date('Y-m-d', strtotime($i['warranty_date'] . ' +' . (int) $i['warranty_duration'] . ' months'));
            if ($end >= date('Y-m-d') && $end <= $limit) {
                $warranty[] = ['asset_id' => self::assetIdOf($i['itemtype'], (int) $i['items_id']), 'label' => 'Warranty ends ' . $end, 'link' => ''];
            }
        }

        $lines_free = [];
        foreach ($DB->request(['SELECT' => ['id', 'name'], 'FROM' => 'sanam_lines', 'WHERE' => ['users_id' => 0, 'is_deleted' => 0]]) as $l) {
            $linked = $DB->request(['COUNT' => 'cnt', 'FROM' => 'sanam_items_lines', 'WHERE' => ['lines_id' => $l['id']]]);
            $has = 0;
            foreach ($linked as $c) {
                $has = (int) $c['cnt'];
            }
            if ($has === 0) {
                $lines_free[] = ['asset_id' => '', 'label' => (string) $l['name'], 'link' => ''];
            }
        }

        $missing_labels = [];
        foreach (Label::missing() as $asset_id) {
            $missing_labels[] = ['asset_id' => $asset_id, 'label' => 'Label not printed', 'link' => ''];
        }

        $open_conflicts = [];
        foreach (Conflict::listOpen() as $c) {
            $open_conflicts[] = ['asset_id' => '', 'label' => $c['itemtype'] . ' #' . $c['items_id'] . ' vs ' . $c['other_itemtype'] . ' #' . $c['other_items_id'] . ' (' . $c['field'] . ')', 'link' => ''];
        }

        return [
            'missing_location'  => self::block('Assets without a location', $missing_location),
            'missing_custodian' => self::block('Assets without a custodian', $missing_custodian),
            'unverified'        => self::block('Not verified in the last closed campaign', $unverified),
            'overdue'           => self::block('Temporary assignments past return date', $overdue),
            'repairs'           => self::block('Assets awaiting repair', $repairs),
            'warranty'          => self::block('Warranty ending within 60 days', $warranty),
            'lines_free'        => self::block('Phone lines with no owner or device', $lines_free),
            'labels'            => self::block('Labels not printed yet', $missing_labels),
            'conflicts'         => self::block('Identifier conflicts to review', $open_conflicts),
        ];
    }

    /** @return list<int> */
    private static function repairStateIds(): array
    {
        global $DB;

        $ids = [];
        foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_states', 'WHERE' => ['name' => ['LIKE', '%repair%']]]) as $row) {
            $ids[] = (int) $row['id'];
        }
        return $ids;
    }

    private static function lastClosedCampaignId(): ?int
    {
        global $DB;

        foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_plugin_sanamassets_campaigns', 'WHERE' => ['status' => 'closed'], 'ORDER' => ['closed_at DESC'], 'LIMIT' => 1]) as $row) {
            return (int) $row['id'];
        }
        return null;
    }

    private static function row(array $identity): array
    {
        return ['asset_id' => $identity['asset_id'], 'label' => (string) $identity['item']->fields['name'], 'link' => ''];
    }

    private static function assetIdOf(string $itemtype, int $items_id): string
    {
        $item = Items::load($itemtype, $items_id);
        return $item ? (string) $item->fields['otherserial'] : '';
    }

    private static function block(string $label, array $items): array
    {
        return ['label' => $label, 'count' => count($items), 'items' => array_slice($items, 0, 50)];
    }
}
