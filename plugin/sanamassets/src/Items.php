<?php

namespace SanamPlugin\Sanamassets;

use CommonDBTM;

/** Loads managed items by their identity mapping. */
final class Items
{
    /**
     * Every active identity with its item loaded. Items that no longer exist are skipped.
     *
     * @return \Generator<array{asset_id: string, itemtype: string, items_id: int, item: CommonDBTM}>
     */
    public static function activeIdentities(): \Generator
    {
        global $DB;

        foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_assetids', 'WHERE' => ['status' => 'active'], 'ORDER' => ['asset_id ASC']]) as $row) {
            $item = self::load($row['itemtype'], (int) $row['items_id']);
            if ($item === null) {
                continue;
            }
            yield [
                'asset_id' => $row['asset_id'],
                'itemtype' => $row['itemtype'],
                'items_id' => (int) $row['items_id'],
                'item'     => $item,
            ];
        }
    }

    public static function load(string $itemtype, int $id): ?CommonDBTM
    {
        if ($id <= 0 || !class_exists($itemtype)) {
            return null;
        }
        $item = new $itemtype();
        if (!$item->getFromDB($id) || !empty($item->fields['is_deleted'])) {
            return null;
        }
        return $item;
    }

    public static function byAssetId(string $asset_id): ?array
    {
        $identity = AssetId::find($asset_id);
        if ($identity === null || $identity['status'] !== 'active') {
            return null;
        }
        $item = self::load($identity['itemtype'], (int) $identity['items_id']);
        if ($item === null) {
            return null;
        }
        return [
            'asset_id' => $asset_id,
            'itemtype' => $identity['itemtype'],
            'items_id' => (int) $identity['items_id'],
            'item'     => $item,
        ];
    }
}
