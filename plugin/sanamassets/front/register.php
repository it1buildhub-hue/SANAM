<?php

/**
 * Draft registration during an audit. Auditors can add an item that has no record yet.
 * The item is created as "Draft (to enrich)" and gets the next SANAM identity. Duplicate
 * serials are still refused by the identity hook, so the same device is never created twice.
 */

include('../../../inc/includes.php');

global $DB;

use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
$can_audit = Session::haveRight(Schema::RIGHT_AUDIT, UPDATE) || Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);
if (!$can_audit) {
    Session::checkRight(Schema::RIGHT_AUDIT, UPDATE);
}

// Native types an auditor may create as drafts, when SANAM grants create rights for them.
$types = [
    'Computer'         => __('Computer or laptop', 'sanamassets'),
    'Monitor'          => __('Monitor or display', 'sanamassets'),
    'Peripheral'       => __('Peripheral', 'sanamassets'),
    'Phone'            => __('Phone or tablet', 'sanamassets'),
    'Printer'          => __('Printer', 'sanamassets'),
    'NetworkEquipment' => __('Network equipment', 'sanamassets'),
];
$allowed = [];
foreach ($types as $class => $label) {
    if (Session::haveRight($class::$rightname, CREATE)) {
        $allowed[$class] = $label;
    }
}

$draft_state = 0;
foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_states', 'WHERE' => ['name' => 'Draft (to enrich)'], 'LIMIT' => 1]) as $row) {
    $draft_state = (int) $row['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = (string) ($_POST['itemtype'] ?? '');
    $name = trim((string) ($_POST['name'] ?? ''));
    $serial = trim((string) ($_POST['serial'] ?? ''));
    if (!empty($_POST['not_visible'])) {
        $serial = 'NOT-VISIBLE';
    }
    $entity = (int) ($_POST['entities_id'] ?? 0);

    if (!isset($allowed[$class])) {
        Session::addMessageAfterRedirect(__('You cannot create this type of asset.', 'sanamassets'), false, ERROR);
    } elseif ($name === '') {
        Session::addMessageAfterRedirect(__('Give the asset a name.', 'sanamassets'), false, ERROR);
    } elseif ($draft_state === 0) {
        Session::addMessageAfterRedirect(__('The "Draft (to enrich)" state is missing. Run the SANAM seed.', 'sanamassets'), false, ERROR);
    } elseif (!Session::haveAccessToEntity($entity)) {
        Session::addMessageAfterRedirect(__('You do not have access to that company.', 'sanamassets'), false, ERROR);
    } else {
        $item = new $class();
        $id = $item->add([
            'name'        => $name,
            'serial'      => $serial,
            'entities_id' => $entity,
            'states_id'   => $draft_state,
            'locations_id' => (int) ($_POST['locations_id'] ?? 0),
            'comment'     => 'Registered as a draft during an audit',
        ]);
        if ($id === false) {
            // The identity hook already explained why (for example a duplicate serial).
            Html::redirect(Ui::self() . '?serial=' . urlencode($serial));
        }
        $item->getFromDB((int) $id);
        Session::addMessageAfterRedirect(__('Draft created. Complete the details when you have them.', 'sanamassets'), false, INFO);
        Html::redirect('/plugins/sanamassets/front/scan.php?asset=' . urlencode((string) $item->fields['otherserial']));
    }
    Html::redirect(Ui::self());
}

$prefill_serial = trim((string) ($_GET['serial'] ?? ''));

Html::header(__('Register a draft asset', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2' style='max-width:640px'>";
echo "<div class='card'><div class='card-header'><h3 class='card-title'>" . __('Register a draft asset', 'sanamassets') . "</h3></div><div class='card-body'>";
echo "<p class='text-muted small'>" . __('The asset is created as a draft with its own SANAM ID. Complete the other details later. If the serial already exists, the system opens the existing record instead.', 'sanamassets') . "</p>";

if ($allowed === []) {
    echo "<div class='alert alert-info'>" . __('Your role cannot create assets. Ask an asset manager.', 'sanamassets') . "</div>";
} else {
    echo "<form method='post'>" . Ui::csrf();
    echo "<div class='mb-2'><label class='form-label'>" . __('Type', 'sanamassets') . "</label><select class='form-select' name='itemtype'>";
    foreach ($allowed as $class => $label) {
        echo "<option value='" . Ui::e($class) . "'>" . Ui::e($label) . "</option>";
    }
    echo "</select></div>";
    echo "<div class='mb-2'><label class='form-label'>" . __('Name', 'sanamassets') . "</label><input class='form-control' name='name' required maxlength='255'></div>";
    echo "<div class='mb-2'><label class='form-label'>" . __('Serial', 'sanamassets') . "</label><input class='form-control' name='serial' maxlength='255' value='" . Ui::e($prefill_serial) . "'>";
    echo "<div class='form-check mt-1'><input class='form-check-input' type='checkbox' name='not_visible' value='1' id='not-visible'><label class='form-check-label' for='not-visible'>" . __('Serial is not visible on the device', 'sanamassets') . "</label></div></div>";
    echo "<div class='mb-2'><label class='form-label'>" . __('Company', 'sanamassets') . "</label>";
    echo Entity::dropdown(['name' => 'entities_id', 'value' => (int) $_SESSION['sanamactive_entity'], 'display' => false]);
    echo "</div>";
    echo "<button class='btn btn-primary btn-lg w-100' type='submit'>" . __('Create draft', 'sanamassets') . "</button>";
    echo "</form>";
}

echo "</div></div></div>";
Html::footer();
