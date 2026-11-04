<?php

/**
 * Label management: list, print batch, reprint, mark attached or damaged.
 * Printing and reprinting never create a new asset identity.
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\AssetId;
use SanamPlugin\Sanamassets\Items;
use SanamPlugin\Sanamassets\Label;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_change = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$can_change) {
        Html::redirect(Ui::self());
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'print') {
        $ids = array_filter(array_map('strtoupper', (array) ($_POST['ids'] ?? [])), [AssetId::class, 'isValid']);
        if ($ids === []) {
            Session::addMessageAfterRedirect(__('Select at least one asset.', 'sanamassets'), false, WARNING);
            Html::redirect(Ui::self());
        }
        try {
            Label::scanUrl($ids[0]); // refuses loopback addresses before anything is printed
        } catch (RuntimeException $e) {
            Session::addMessageAfterRedirect($e->getMessage(), false, ERROR);
            Html::redirect(Ui::self());
        }
        foreach ($ids as $asset_id) {
            Label::record($asset_id, 'printed', 'Batch print');
        }
        Html::redirect('/plugins/sanamassets/front/labelprint.php?ids=' . urlencode(implode(',', $ids)));
    }

    $asset_id = strtoupper(trim((string) ($_POST['asset'] ?? '')));
    if (Items::byAssetId($asset_id) !== null) {
        if ($action === 'reprint') {
            $reason = trim((string) ($_POST['reason'] ?? ''));
            if ($reason === '') {
                Session::addMessageAfterRedirect(__('Give a reason for reprinting.', 'sanamassets'), false, ERROR);
                Html::redirect(Ui::self());
            }
            Label::record($asset_id, 'reprinted', $reason);
            Html::redirect('/plugins/sanamassets/front/labelprint.php?ids=' . urlencode($asset_id));
        }
        if ($action === 'attached') {
            Label::record($asset_id, 'attached', 'Attached and checked');
        }
        if ($action === 'damaged') {
            Label::record($asset_id, 'damaged', trim((string) ($_POST['reason'] ?? '')));
        }
        Session::addMessageAfterRedirect(__('Label record saved.', 'sanamassets'), false, INFO);
    }
    Html::redirect(Ui::self());
}

Html::header(__('Labels', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2'>";
echo "<div class='card'><div class='card-header'><h3 class='card-title'>" . __('Label status', 'sanamassets') . "</h3></div>";
echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='print'>";
echo "<div class='table-responsive'><table class='table card-table'><thead><tr><th></th><th>" . __('Asset ID', 'sanamassets') . "</th><th>" . __('Name', 'sanamassets') . "</th><th>" . __('Label', 'sanamassets') . "</th><th></th></tr></thead><tbody>";
foreach (Items::activeIdentities() as $identity) {
    $state = Label::status($identity['asset_id']);
    echo "<tr><td><input type='checkbox' name='ids[]' value='" . Ui::e($identity['asset_id']) . "'></td>";
    echo "<td>" . Ui::e($identity['asset_id']) . "</td><td>" . Ui::e($identity['item']->fields['name']) . "</td><td><span class='badge'>" . Ui::e($state) . "</span></td><td></td></tr>";
}
echo "</tbody></table></div>";
if ($can_change) {
    echo "<div class='card-body'><button class='btn btn-primary'>" . __('Print selected labels', 'sanamassets') . "</button></div>";
}
echo "</form></div>";

// One-asset actions, kept outside the batch form (HTML does not allow nested forms).
if ($can_change) {
    echo "<div class='card mt-3'><div class='card-header'><h3 class='card-title'>" . __('One asset', 'sanamassets') . "</h3></div><div class='card-body'>";
    echo "<form method='post' class='row g-2'>" . Ui::csrf();
    echo "<div class='col-md-3'><input class='form-control' name='asset' required placeholder='SNM-000001'></div>";
    echo "<div class='col-md-3'><input class='form-control' name='reason' placeholder='" . Ui::e(__('Reason (for reprint or damage)', 'sanamassets')) . "'></div>";
    echo "<div class='col-md-6 d-flex gap-2 flex-wrap'>";
    echo "<button class='btn btn-outline-secondary' name='action' value='attached'>" . __('Label attached', 'sanamassets') . "</button>";
    echo "<button class='btn btn-outline-danger' name='action' value='damaged'>" . __('Label damaged', 'sanamassets') . "</button>";
    echo "<button class='btn btn-outline-primary' name='action' value='reprint'>" . __('Reprint (same ID)', 'sanamassets') . "</button>";
    echo "</div></form></div></div>";
}
echo "</div>";
Html::footer();
