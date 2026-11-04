<?php

/**
 * Employee custody summary, exit clearance, and printable handover sheet (?print=1).
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\Custody;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_change = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);

$users_id = (int) ($_REQUEST['users_id'] ?? 0);
$user = new User();
if ($users_id === 0 || !$user->getFromDB($users_id)) {
    Html::displayErrorAndDie(__('Employee not found.', 'sanamassets'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($can_change) {
        Ui::run(static fn() => Custody::clear($users_id, trim((string) ($_POST['note'] ?? ''))), __('Clearance granted.', 'sanamassets'));
    }
    Html::redirect(Ui::self() . '?users_id=' . $users_id);
}

if (!empty($_GET['print'])) {
    $sheet = Custody::handoverSheet($users_id);
    Html::nullHeader(__('Handover sheet', 'sanamassets'), Ui::self());
    echo "<div class='container py-3' dir='ltr'>";
    echo "<h2>" . __('Asset handover and return sheet', 'sanamassets') . " <span dir='rtl'>نموذج تسليم واستلام الأصول</span></h2>";
    echo "<p>" . __('Employee', 'sanamassets') . ": <strong>" . Ui::e($sheet['person']) . "</strong> &nbsp; " . __('Date', 'sanamassets') . ": " . Ui::e($sheet['date']) . "</p>";
    echo "<table border='1' cellpadding='6' cellspacing='0' style='width:100%;border-collapse:collapse'><thead><tr><th>Asset ID</th><th>" . __('Item', 'sanamassets') . "</th><th>" . __('Type', 'sanamassets') . "</th><th>" . __('Since', 'sanamassets') . "</th><th>" . __('Expected back', 'sanamassets') . "</th><th>" . __('Accessories', 'sanamassets') . "</th><th>" . __('Condition', 'sanamassets') . "</th></tr></thead><tbody>";
    foreach ($sheet['items'] as $i) {
        echo "<tr><td>" . Ui::e($i['asset_id']) . "</td><td>" . Ui::e($i['name']) . "</td><td>" . Ui::e($i['kind']) . "</td><td>" . Ui::e(substr($i['since'], 0, 10)) . "</td><td>" . Ui::e($i['expected']) . "</td><td>" . Ui::e($i['accessories']) . "</td><td>&nbsp;</td></tr>";
    }
    echo "</tbody></table>";
    echo "<p style='margin-top:40px'>" . __('I confirm receipt / return of the assets listed above in the condition stated.', 'sanamassets') . "<br><span dir='rtl'>أقر باستلام / إعادة الأصول المذكورة أعلاه بالحالة الموضحة.</span></p>";
    echo "<p style='margin-top:40px'>______________________ &nbsp;&nbsp;&nbsp; " . __('Employee signature', 'sanamassets') . " &nbsp;&nbsp;&nbsp;&nbsp; ______________________ &nbsp;&nbsp;&nbsp; " . __('IT signature', 'sanamassets') . "</p>";
    echo "</div>";
    Html::nullFooter();
    exit;
}

Html::header(__('Employee custody', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2'>";
echo "<div class='card mb-3'><div class='card-body'>";
$full_name = trim($user->fields['firstname'] . ' ' . $user->fields['realname']);
echo "<h2 class='mb-1'>" . Ui::e($full_name !== '' ? $full_name : $user->fields['name']) . "</h2>";
echo "<div class='text-muted'>" . Ui::e($user->fields['name']) . "</div>";
echo "<div class='mt-2 d-flex gap-2'><a class='btn btn-outline-secondary' target='_blank' href='?users_id=" . $users_id . "&print=1'>" . __('Print handover sheet', 'sanamassets') . "</a></div>";
echo "</div></div>";

$open = Custody::outstanding($users_id);
echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Outstanding', 'sanamassets') . "</h3></div><div class='list-group list-group-flush'>";
if ($open['assignments'] === [] && $open['lines'] === []) {
    echo "<div class='list-group-item text-success'>" . __('Nothing outstanding.', 'sanamassets') . "</div>";
}
foreach ($open['assignments'] as $a) {
    echo "<div class='list-group-item'>" . Ui::e($a['asset_id']) . " · " . Ui::e($a['kind']) . " · " . Ui::e(substr((string) $a['date_start'], 0, 10)) . "</div>";
}
foreach ($open['lines'] as $l) {
    echo "<div class='list-group-item'>" . __('Phone line', 'sanamassets') . ": " . Ui::e($l['name']) . "</div>";
}
echo "</div></div>";

if ($can_change) {
    echo "<div class='card'><div class='card-body'><form method='post'>" . Ui::csrf();
    echo "<div class='mb-2'><input class='form-control' name='note' placeholder='" . Ui::e(__('Clearance note', 'sanamassets')) . "'></div>";
    echo "<button class='btn btn-success' " . (($open['assignments'] !== [] || $open['lines'] !== []) ? 'disabled' : '') . ">" . __('Grant exit clearance', 'sanamassets') . "</button>";
    echo "</form></div></div>";
}

echo "</div>";
Html::footer();
