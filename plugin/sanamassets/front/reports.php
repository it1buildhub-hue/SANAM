<?php

/**
 * Reports and exports. The CSV files are produced by export.php, which checks its own rights.
 */

include('../../../inc/includes.php');

global $DB;

use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_export = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);

Html::header(__('Reports and exports', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2'>";
echo "<div class='card'><div class='card-header'><h3 class='card-title'>" . __('Reports', 'sanamassets') . "</h3></div><div class='card-body'>";
echo "<p class='text-muted'>" . __('Break down by company, location, department, employee, category and status with the filters in the SANAM search. Exports below are CSV files that open in Excel.', 'sanamassets') . "</p>";

if ($can_export) {
    echo "<div class='d-flex flex-wrap gap-2 mb-3'>";
    echo "<a class='btn btn-outline-primary' href='/plugins/sanamassets/front/export.php?type=inventory'>" . __('Inventory (CSV)', 'sanamassets') . "</a>";
    echo "<a class='btn btn-outline-primary' href='/plugins/sanamassets/front/export.php?type=assignments'>" . __('Custody history (CSV)', 'sanamassets') . "</a>";
    echo "</div>";

    echo "<form method='get' action='/plugins/sanamassets/front/export.php' class='d-flex gap-2'><input type='hidden' name='type' value='discrepancies'>";
    echo "<select class='form-select' name='campaign'>";
    foreach ($DB->request(['SELECT' => ['id', 'name'], 'FROM' => 'sanam_plugin_sanamassets_campaigns', 'ORDER' => ['id DESC']]) as $c) {
        echo "<option value='" . (int) $c['id'] . "'>" . Ui::e($c['name']) . "</option>";
    }
    echo "</select><button class='btn btn-outline-primary'>" . __('Findings of a campaign (CSV)', 'sanamassets') . "</button></form>";
} else {
    echo "<div class='alert alert-info'>" . __('Exports need asset-manager rights.', 'sanamassets') . "</div>";
}

echo "</div></div></div>";
Html::footer();
