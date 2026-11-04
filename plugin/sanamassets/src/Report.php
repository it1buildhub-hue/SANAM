<?php

namespace SanamPlugin\Sanamassets;

use Dropdown;

/**
 * Exports. Each report returns header + rows; Csv turns them into a safe file.
 */
final class Report
{
    /** @return array{0: list<string>, 1: list<list<string>>} */
    public static function inventory(): array
    {
        global $DB;

        $header = ['Asset ID', 'Type', 'Name', 'Serial', 'Company', 'Location', 'Custodian', 'Status', 'Label', 'Last verified'];
        $rows = [];
        foreach (Items::activeIdentities() as $identity) {
            $f = $identity['item']->fields;
            $last = '';
            foreach ($DB->request(['SELECT' => ['date_verified'], 'FROM' => 'sanam_plugin_sanamassets_verifications', 'WHERE' => ['asset_id' => $identity['asset_id']], 'ORDER' => ['date_verified DESC'], 'LIMIT' => 1]) as $v) {
                $last = (string) $v['date_verified'];
            }
            $rows[] = [
                $identity['asset_id'],
                $identity['itemtype'],
                (string) $f['name'],
                (string) ($f['serial'] ?? ''),
                Dropdown::getDropdownName('sanam_entities', (int) $f['entities_id']),
                $f['locations_id'] ? Dropdown::getDropdownName('sanam_locations', (int) $f['locations_id']) : '',
                $f['users_id'] ? getUserName((int) $f['users_id']) : '',
                $f['states_id'] ? Dropdown::getDropdownName('sanam_states', (int) $f['states_id']) : '',
                Label::status($identity['asset_id']),
                $last,
            ];
        }
        return [$header, $rows];
    }

    /** @return array{0: list<string>, 1: list<list<string>>} */
    public static function discrepancies(int $campaign_id): array
    {
        $header = ['Type', 'Asset ID', 'Details', 'Status', 'Reviewed'];
        $rows = [];
        foreach (Discrepancy::forCampaign($campaign_id) as $d) {
            $rows[] = [(string) $d['type'], (string) ($d['asset_id'] ?? ''), (string) ($d['details'] ?? ''), (string) $d['status'], (string) ($d['date_reviewed'] ?? '')];
        }
        return [$header, $rows];
    }

    /** @return array{0: list<string>, 1: list<list<string>>} */
    public static function assignments(): array
    {
        global $DB;

        $header = ['Asset ID', 'Kind', 'Holder', 'Status', 'Since', 'Expected return', 'Returned', 'Condition issue', 'Condition return'];
        $rows = [];
        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_assignments', 'ORDER' => ['id DESC']]) as $a) {
            $rows[] = [
                (string) $a['asset_id'],
                (string) $a['kind'],
                $a['users_id'] ? getUserName((int) $a['users_id']) : '',
                (string) $a['status'],
                (string) $a['date_start'],
                (string) ($a['date_expected_return'] ?? ''),
                (string) ($a['date_returned'] ?? ''),
                (string) ($a['condition_issue'] ?? ''),
                (string) ($a['condition_return'] ?? ''),
            ];
        }
        return [$header, $rows];
    }
}
