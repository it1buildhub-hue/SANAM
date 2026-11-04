<?php

/**
 * Daily workspace: actionable lists, identifier conflict review, and plugin settings link.
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\Conflict;
use SanamPlugin\Sanamassets\Dashboard;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_change = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($can_change && ($_POST['action'] ?? '') === 'conflict') {
        Ui::run(static fn() => Conflict::review((int) $_POST['id'], (string) $_POST['decision']), __('Conflict reviewed.', 'sanamassets'));
    }
    Html::redirect(Ui::self());
}

Html::header(__('SANAM dashboard', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2'>";
echo "<div class='d-flex justify-content-between align-items-center mb-3'><h2 class='mb-0'>" . __('What needs attention', 'sanamassets') . "</h2>";
echo "<a class='btn btn-outline-secondary' href='/plugins/sanamassets/front/config.php'>" . __('Settings', 'sanamassets') . "</a></div>";

echo "<div class='row row-cards'>";
foreach (Dashboard::compute() as $key => $block) {
    echo "<div class='col-md-6 col-xl-4'><div class='card h-100'><div class='card-header'><h3 class='card-title'>" . Ui::e($block['label']) . "</h3><div class='card-actions'><span class='badge " . ($block['count'] > 0 ? 'bg-orange' : 'bg-green') . "'>" . $block['count'] . "</span></div></div>";
    echo "<div class='list-group list-group-flush' style='max-height:260px;overflow:auto'>";
    if ($block['items'] === []) {
        echo "<div class='list-group-item text-muted'>" . __('Nothing here.', 'sanamassets') . "</div>";
    }
    foreach ($block['items'] as $row) {
        echo "<div class='list-group-item small'>" . ($row['asset_id'] !== '' ? "<strong>" . Ui::e($row['asset_id']) . "</strong> " : '') . Ui::e($row['label']) . "</div>";
    }
    echo "</div></div></div>";
}
echo "</div>";

// Identifier conflicts are reviewed here: accepted (both kept) or rejected (not a real conflict).
$conflicts = Conflict::listOpen();
if ($conflicts !== [] && $can_change) {
    echo "<div class='card mt-3'><div class='card-header'><h3 class='card-title'>" . __('Identifier conflicts', 'sanamassets') . "</h3></div><div class='table-responsive'><table class='table card-table'><tbody>";
    foreach ($conflicts as $c) {
        echo "<tr><td>" . Ui::e($c['itemtype']) . " #" . (int) $c['items_id'] . " ↔ " . Ui::e($c['other_itemtype']) . " #" . (int) $c['other_items_id'] . "</td><td>" . Ui::e($c['field']) . ": " . Ui::e($c['value']) . "</td><td>";
        echo "<form method='post' class='d-flex gap-1'>" . Ui::csrf() . "<input type='hidden' name='action' value='conflict'><input type='hidden' name='id' value='" . (int) $c['id'] . "'>";
        echo "<button name='decision' value='accepted' class='btn btn-sm btn-outline-secondary'>" . __('Both are real', 'sanamassets') . "</button>";
        echo "<button name='decision' value='rejected' class='btn btn-sm btn-outline-success'>" . __('Not a conflict', 'sanamassets') . "</button></form></td></tr>";
    }
    echo "</tbody></table></div></div>";
}

echo "</div>";
Html::footer();
