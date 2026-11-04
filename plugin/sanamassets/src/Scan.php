<?php

namespace SanamPlugin\Sanamassets;

use Session;

/**
 * Scan log. Every scan is kept, including duplicates and unknown codes.
 * A scan is only a lookup. Verification is a separate, explicit action.
 */
final class Scan
{
    public static function record(?int $campaign_id, string $asset_id, string $method, string $result): void
    {
        global $DB;

        $DB->insert('sanam_plugin_sanamassets_scans', [
            'campaigns_id' => $campaign_id ?: null,
            'asset_id'     => $asset_id,
            'query'        => $asset_id,
            'users_id'     => (int) Session::getLoginUserID(false),
            'method'       => $method,
            'result'       => $result,
            'date_scan'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Find items by asset identity, serial, or name. Used by manual search and the scan page.
     *
     * @return list<array{asset_id: string, itemtype: string, items_id: int, name: string, serial: string}>
     */
    public static function search(string $query): array
    {
        global $DB;

        $query = trim($query);
        if ($query === '') {
            return [];
        }

        if (AssetId::isValid(strtoupper($query))) {
            $identity = Items::byAssetId(strtoupper($query));
            if ($identity !== null) {
                return [self::summary($identity)];
            }
            return [];
        }

        $results = [];
        foreach (AssetHooks::searchableTypes() as $class) {
            $table = (new $class())->getTable();
            $where = [
                'is_deleted' => 0,
                'OR' => [
                    'serial'  => ['LIKE', '%' . $query . '%'],
                    'name'    => ['LIKE', '%' . $query . '%'],
                ],
            ];
            foreach ($DB->request(['SELECT' => ['id'], 'FROM' => $table, 'WHERE' => $where, 'LIMIT' => 25]) as $row) {
                $identity = self::identityOf($class, (int) $row['id']);
                if ($identity !== null) {
                    $results[] = self::summary($identity);
                }
            }
        }
        return $results;
    }

    private static function identityOf(string $itemtype, int $items_id): ?array
    {
        $identity = Items::load($itemtype, $items_id);
        if ($identity === null) {
            return null;
        }
        $asset_id = (string) $identity->fields['otherserial'];
        return Items::byAssetId($asset_id);
    }

    private static function summary(array $identity): array
    {
        return [
            'asset_id' => $identity['asset_id'],
            'itemtype' => $identity['itemtype'],
            'items_id' => $identity['items_id'],
            'name'     => (string) $identity['item']->fields['name'],
            'serial'   => (string) ($identity['item']->fields['serial'] ?? ''),
        ];
    }
}
