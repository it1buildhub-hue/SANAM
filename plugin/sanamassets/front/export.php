<?php

/**
 * CSV export. Requires asset-manager update rights because exports leave the system.
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\Report;
use SanamPlugin\Sanamassets\Schema;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, UPDATE);

$type = (string) ($_GET['type'] ?? '');
switch ($type) {
    case 'inventory':
        [$header, $rows] = Report::inventory();
        $name = 'inventory';
        break;
    case 'assignments':
        [$header, $rows] = Report::assignments();
        $name = 'assignments';
        break;
    case 'discrepancies':
        [$header, $rows] = Report::discrepancies((int) ($_GET['campaign'] ?? 0));
        $name = 'discrepancies';
        break;
    default:
        Html::displayErrorAndDie(__('Unknown export.', 'sanamassets'));
}

/** Cells that start with a formula character are neutralised so Excel cannot run them. */
$safe = static function (string $cell): string {
    return preg_match('/^[=+\-@\t\r]/', $cell) === 1 ? "'" . $cell : $cell;
};

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="sanam-' . $name . '-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens Arabic names correctly
fputcsv($out, $header);
foreach ($rows as $row) {
    fputcsv($out, array_map(static fn($c) => $safe((string) $c), $row));
}
fclose($out);
