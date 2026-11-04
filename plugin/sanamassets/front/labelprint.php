<?php

/**
 * Printable QR labels. Opened from the label page; prints without the SANAM chrome.
 * Each label shows the company, the readable asset ID, and a QR to the stable scan URL.
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\AssetId;
use SanamPlugin\Sanamassets\Items;
use SanamPlugin\Sanamassets\Label;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);

$ids = array_filter(array_map('strtoupper', explode(',', (string) ($_GET['ids'] ?? ''))), [AssetId::class, 'isValid']);

Html::nullHeader(__('Labels', 'sanamassets'), Ui::self());
?>
<style>
    @page { margin: 4mm; }
    .sanam-sheet { display: flex; flex-wrap: wrap; gap: 3mm; }
    .sanam-label { width: 50mm; height: 25mm; border: 0.2mm solid #000; box-sizing: border-box;
        display: flex; align-items: center; padding: 1.5mm; gap: 1.5mm; font-family: Arial, sans-serif;
        break-inside: avoid; }
    .sanam-label svg { width: 21mm; height: 21mm; flex: none; }
    .sanam-label .txt { overflow: hidden; line-height: 1.15; }
    .sanam-label .co { font-size: 6pt; text-transform: uppercase; }
    .sanam-label .id { font-size: 10pt; font-weight: 700; }
    .sanam-label .nm { font-size: 6.5pt; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    @media print { .no-print { display: none; } }
</style>
<?php
echo "<div class='container py-2'>";
echo "<p class='no-print'><button onclick='window.print()'>" . __('Print', 'sanamassets') . "</button></p>";

if ($ids === []) {
    echo "<p>" . __('No valid asset IDs.', 'sanamassets') . "</p>";
} else {
    try {
        echo "<div class='sanam-sheet'>";
        foreach ($ids as $asset_id) {
            $identity = Items::byAssetId($asset_id);
            if ($identity === null) {
                continue;
            }
            $url = Label::scanUrl($asset_id);
            $company = Dropdown::getDropdownName('sanam_entities', (int) $identity['item']->fields['entities_id']);
            echo "<div class='sanam-label'>";
            echo Label::qrSvg($url);
            echo "<div class='txt'><div class='co'>SANAM · " . Ui::e($company) . "</div>";
            echo "<div class='id'>" . Ui::e($asset_id) . "</div>";
            echo "<div class='nm'>" . Ui::e($identity['item']->fields['name']) . "</div></div>";
            echo "</div>";
        }
        echo "</div>";
    } catch (RuntimeException $e) {
        echo "<div class='alert alert-danger'>" . Ui::e($e->getMessage()) . "</div>";
    }
}
echo "</div>";
Html::nullFooter();
