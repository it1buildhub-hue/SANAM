<?php

/**
 * Custody: issue, return and transfer assets. Only asset managers can change custody.
 */

include('../../../inc/includes.php');

global $DB;

use SanamPlugin\Sanamassets\Custody;
use SanamPlugin\Sanamassets\Items;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_change = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$can_change) {
        Session::addMessageAfterRedirect(__('You cannot change custody.', 'sanamassets'), false, ERROR);
        Html::redirect(Ui::self());
    }

    switch ((string) ($_POST['action'] ?? '')) {
        case 'issue':
            $identity = Items::byAssetId(strtoupper(trim((string) $_POST['asset'])));
            if ($identity === null) {
                Session::addMessageAfterRedirect(__('Unknown asset identity.', 'sanamassets'), false, ERROR);
                break;
            }
            Ui::run(static fn() => Custody::issue($identity['itemtype'], $identity['items_id'], [
                'kind'            => (string) $_POST['kind'],
                'users_id'        => (int) ($_POST['users_id'] ?? 0),
                'groups_id'       => (int) ($_POST['groups_id'] ?? 0),
                'expected_return' => (string) ($_POST['expected_return'] ?? ''),
                'condition'       => (string) ($_POST['condition'] ?? ''),
                'accessories'     => trim((string) ($_POST['accessories'] ?? '')),
                'ack'             => (string) ($_POST['ack'] ?? 'none'),
                'note'            => trim((string) ($_POST['note'] ?? '')),
            ]), __('Asset issued.', 'sanamassets'));
            break;

        case 'return':
            Ui::run(static fn() => Custody::returnAssignment(
                (int) $_POST['assignment_id'],
                (string) ($_POST['condition'] ?? ''),
                trim((string) ($_POST['accessories'] ?? '')),
                trim((string) ($_POST['note'] ?? '')),
            ), __('Asset returned.', 'sanamassets'));
            break;

        case 'transfer':
            Ui::run(static fn() => Custody::transfer(
                (int) $_POST['assignment_id'],
                (int) $_POST['users_id'],
                (string) ($_POST['condition'] ?? ''),
                trim((string) ($_POST['note'] ?? '')),
            ), __('Asset transferred.', 'sanamassets'));
            break;
    }
    Html::redirect(Ui::self());
}

Html::header(__('Custody', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2'>";

$prefill = strtoupper(trim((string) ($_GET['asset'] ?? '')));
$conditions = ['New', 'Good', 'Fair', 'Damaged', 'Not working'];

if ($can_change) {
    echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Issue an asset', 'sanamassets') . "</h3></div><div class='card-body'>";
    echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='issue'>";
    echo "<div class='row g-2'>";
    echo "<div class='col-md-3'><input class='form-control' name='asset' required value='" . Ui::e($prefill) . "' placeholder='SNM-000001'></div>";
    echo "<div class='col-md-3'>" . User::dropdown(['name' => 'users_id', 'value' => 0, 'display_emptychoice' => true, 'right' => 'all', 'display' => false]) . "</div>";
    echo "<div class='col-md-2'><select class='form-select' name='kind'><option value='permanent'>" . __('Permanent', 'sanamassets') . "</option><option value='temporary'>" . __('Temporary', 'sanamassets') . "</option><option value='shared'>" . __('Shared', 'sanamassets') . "</option></select></div>";
    echo "<div class='col-md-2'><input class='form-control' type='date' name='expected_return' aria-label='" . Ui::e(__('Expected return (temporary)', 'sanamassets')) . "'></div>";
    echo "<div class='col-md-2'><select class='form-select' name='condition'>";
    foreach ($conditions as $c) {
        echo "<option>" . Ui::e($c) . "</option>";
    }
    echo "</select></div>";
    echo "<div class='col-md-4'><input class='form-control' name='accessories' placeholder='" . Ui::e(__('Accessories given, e.g. charger, bag', 'sanamassets')) . "'></div>";
    echo "<div class='col-md-3'><select class='form-select' name='ack'><option value='signed_paper'>" . __('Signed paper', 'sanamassets') . "</option><option value='screen'>" . __('Signed on screen', 'sanamassets') . "</option><option value='account'>" . __('Acknowledged in account', 'sanamassets') . "</option><option value='none'>" . __('No acknowledgment', 'sanamassets') . "</option></select></div>";
    echo "<div class='col-md-3'><input class='form-control' name='note' placeholder='" . Ui::e(__('Note', 'sanamassets')) . "'></div>";
    echo "<div class='col-md-2'><button class='btn btn-primary w-100'>" . __('Issue', 'sanamassets') . "</button></div>";
    echo "</div></form></div></div>";
}

echo "<div class='card'><div class='card-header'><h3 class='card-title'>" . __('Active assignments', 'sanamassets') . "</h3></div><div class='table-responsive'><table class='table card-table'><thead><tr><th>" . __('Asset', 'sanamassets') . "</th><th>" . __('Holder', 'sanamassets') . "</th><th>" . __('Type', 'sanamassets') . "</th><th>" . __('Since', 'sanamassets') . "</th><th>" . __('Expected back', 'sanamassets') . "</th><th></th></tr></thead><tbody>";
foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_assignments', 'WHERE' => ['status' => 'active'], 'ORDER' => ['date_start DESC']]) as $a) {
    $overdue = $a['kind'] === 'temporary' && $a['date_expected_return'] && $a['date_expected_return'] < date('Y-m-d');
    echo "<tr" . ($overdue ? " class='table-danger'" : "") . "><td>" . Ui::e($a['asset_id']) . "</td><td>" . Ui::e($a['users_id'] ? getUserName((int) $a['users_id']) : Dropdown::getDropdownName('sanam_groups', (int) $a['groups_id'])) . "</td><td>" . Ui::e($a['kind']) . "</td><td>" . Ui::e(substr((string) $a['date_start'], 0, 10)) . "</td><td>" . Ui::e($a['date_expected_return'] ?? '') . "</td><td>";
    if ($can_change) {
        echo "<form method='post' class='d-flex gap-1 flex-wrap'>" . Ui::csrf() . "<input type='hidden' name='assignment_id' value='" . (int) $a['id'] . "'>";
        echo "<input class='form-control form-control-sm' name='condition' placeholder='" . Ui::e(__('Condition', 'sanamassets')) . "' style='width:110px'>";
        echo "<button class='btn btn-sm btn-outline-success' name='action' value='return'>" . __('Return', 'sanamassets') . "</button>";
        echo User::dropdown(['name' => 'users_id', 'value' => 0, 'display_emptychoice' => true, 'right' => 'all', 'display' => false]);
        echo "<button class='btn btn-sm btn-outline-primary' name='action' value='transfer'>" . __('Transfer', 'sanamassets') . "</button></form>";
    }
    echo "</td></tr>";
}
echo "</tbody></table></div></div>";
echo "</div>";
Html::footer();
