<?php

/**
 * Telecom lines on a phone-sized layout: who holds each line, and which lines are still unassigned.
 * Lines are SANAM's native Line objects; this page only makes them quick to manage.
 */

include('../../../inc/includes.php');

global $DB;

use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_change = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE) && Session::haveRight('line', UPDATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$can_change) {
        Session::addMessageAfterRedirect(__('You cannot change telecom lines.', 'sanamassets'), false, ERROR);
    } elseif (($_POST['action'] ?? '') === 'assign') {
        $line_id = (int) ($_POST['line_id'] ?? 0);
        $users_id = (int) ($_POST['users_id'] ?? 0);
        Ui::run(static function () use ($line_id, $users_id): void {
            $line = new Line();
            if (!$line->getFromDB($line_id)) {
                throw new RuntimeException(__('Line not found.', 'sanamassets'));
            }
            $line->update(['id' => $line_id, 'users_id' => $users_id]);
        }, __('Line owner saved.', 'sanamassets'));
    } elseif (($_POST['action'] ?? '') === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $number = trim((string) ($_POST['caller_num'] ?? ''));
        $operator = (int) ($_POST['lineoperators_id'] ?? 0);
        $entity = (int) ($_POST['entities_id'] ?? 0);
        Ui::run(static function () use ($name, $number, $operator, $entity): void {
            if ($name === '' && $number === '') {
                throw new RuntimeException(__('Enter the number or a name for the line.', 'sanamassets'));
            }
            if (!Session::haveAccessToEntity($entity)) {
                throw new RuntimeException(__('You do not have access to that company.', 'sanamassets'));
            }
            $line = new Line();
            // The number is stored as text: leading zeros and + signs must survive.
            $line->add([
                'name'            => $name !== '' ? $name : $number,
                'caller_num'      => $number,
                'lineoperators_id' => $operator,
                'entities_id'     => $entity,
            ]);
        }, __('Line added.', 'sanamassets'));
    }
    Html::redirect(Ui::self());
}

Html::header(__('Telecom lines', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2' style='max-width:720px'>";

$unassigned = 0;
$rows = [];
foreach ($DB->request(['SELECT' => ['id', 'name', 'caller_num', 'users_id', 'lineoperators_id', 'entities_id'], 'FROM' => 'sanam_lines', 'WHERE' => ['is_deleted' => 0], 'ORDER' => ['name ASC']]) as $row) {
    $rows[] = $row;
    if ((int) $row['users_id'] === 0) {
        $unassigned++;
    }
}

echo "<div class='d-flex justify-content-between align-items-center mb-2'><h2 class='mb-0'>" . __('Telecom lines', 'sanamassets') . "</h2>";
echo "<span class='badge " . ($unassigned > 0 ? 'bg-orange' : 'bg-green') . "'>" . $unassigned . " " . __('without owner', 'sanamassets') . "</span></div>";

if ($rows === []) {
    echo "<div class='alert alert-info'>" . __('No lines yet.', 'sanamassets') . "</div>";
}

foreach ($rows as $row) {
    $owner = (int) $row['users_id'] > 0 ? getUserName((int) $row['users_id']) : __('No owner', 'sanamassets');
    echo "<div class='card mb-2'><div class='card-body'>";
    echo "<div class='d-flex justify-content-between'><strong>" . Ui::e($row['caller_num'] !== '' ? $row['caller_num'] : $row['name']) . "</strong>";
    echo "<span class='text-muted small'>" . Ui::e(Dropdown::getDropdownName('sanam_lineoperators', (int) $row['lineoperators_id'])) . "</span></div>";
    echo "<div class='small text-muted mb-2'>" . Ui::e($row['name']) . " · " . Ui::e(Dropdown::getDropdownName('sanam_entities', (int) $row['entities_id'])) . "</div>";
    echo "<div class='mb-2'>" . Ui::e(__('Owner', 'sanamassets')) . ": <strong>" . Ui::e($owner) . "</strong></div>";
    if ($can_change) {
        echo "<form method='post' class='d-flex gap-2'>" . Ui::csrf();
        echo "<input type='hidden' name='action' value='assign'><input type='hidden' name='line_id' value='" . (int) $row['id'] . "'>";
        echo "<div class='flex-grow-1'>" . User::dropdown(['name' => 'users_id', 'value' => (int) $row['users_id'], 'display_emptychoice' => true, 'right' => 'all', 'display' => false]) . "</div>";
        echo "<button class='btn btn-outline-primary'>" . __('Save', 'sanamassets') . "</button></form>";
    }
    echo "</div></div>";
}

if ($can_change) {
    echo "<div class='card mt-3'><div class='card-header'><h3 class='card-title'>" . __('Add a line', 'sanamassets') . "</h3></div><div class='card-body'>";
    echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='create'>";
    echo "<div class='mb-2'><input class='form-control' name='caller_num' maxlength='255' placeholder='" . Ui::e(__('Phone number, e.g. +971 50 000 0000', 'sanamassets')) . "'></div>";
    echo "<div class='mb-2'><input class='form-control' name='name' maxlength='255' placeholder='" . Ui::e(__('Name (optional)', 'sanamassets')) . "'></div>";
    echo "<div class='mb-2'>" . Dropdown::show('LineOperator', ['name' => 'lineoperators_id', 'display' => false, 'display_emptychoice' => true]) . "</div>";
    echo "<div class='mb-2'>" . Entity::dropdown(['name' => 'entities_id', 'value' => (int) $_SESSION['sanamactive_entity'], 'display' => false]) . "</div>";
    echo "<button class='btn btn-primary w-100'>" . __('Add line', 'sanamassets') . "</button></form></div></div>";
}

echo "</div>";
Html::footer();
