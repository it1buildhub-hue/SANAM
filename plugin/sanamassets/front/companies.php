<?php

/**
 * Companies under the root entity. Each company has a display name, shown in the page title, and a
 * logo, shown in the header. A new company is created under the parent chosen in the form.
 */

include('../../../inc/includes.php');

global $DB;

use SanamPlugin\Sanamassets\Brand;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_update = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // The kernel has already checked the CSRF token of this POST. It must not be checked again here.
    Session::checkRight(Schema::RIGHT_MANAGE, UPDATE);

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = (string) ($_POST['name'] ?? '');
        $parent = (int) ($_POST['parent'] ?? 0);
        Ui::run(static function () use ($name, $parent): void {
            if (!Entity::canCreate()) {
                throw new RuntimeException(__('You are not allowed to create companies.', 'sanamassets'));
            }
            if (!Session::haveAccessToEntity($parent)) {
                throw new RuntimeException(__('You do not have access to that parent company.', 'sanamassets'));
            }
            Brand::addCompany($name, $parent);
        }, __('Company added.', 'sanamassets'));
    } elseif ($action === 'save') {
        $id = (int) ($_POST['entities_id'] ?? -1);
        $display = (string) ($_POST['display_name'] ?? '');

        $tmp = null;
        $original = null;
        $upload = $_FILES['logo'] ?? null;
        if (is_array($upload) && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ((int) $upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $upload['tmp_name'])) {
                Session::addMessageAfterRedirect(__('The logo could not be uploaded. Check its size and try again.', 'sanamassets'), false, ERROR);
                Html::redirect(Ui::self());
            }
            $tmp = (string) $upload['tmp_name'];
            $original = (string) $upload['name'];
        }

        Ui::run(static function () use ($id, $display, $tmp, $original): void {
            if (!Session::haveAccessToEntity($id)) {
                throw new RuntimeException(__('You do not have access to that company.', 'sanamassets'));
            }
            Brand::save($id, $display, $tmp, $original);
        }, __('Company saved.', 'sanamassets'));
    }

    Html::redirect(Ui::self());
}

// Companies under the root entity that this profile can access, in tree order.
$entities = [];
foreach ($DB->request(['FROM' => Entity::getTable(), 'ORDER' => 'completename']) as $row) {
    if (Session::haveAccessToEntity((int) $row['id'])) {
        $entities[] = $row;
    }
}
$can_create = $can_update && Entity::canCreate();

Html::header(__('Companies', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2' style='max-width:960px'>";
echo "<p class='text-muted'>" . __('Each company has a display name, shown in the page title, and a logo, shown in the header. Logos are PNG, JPEG or WebP files of up to 1 MB, between 32 and 2000 pixels on each side.', 'sanamassets') . "</p>";

if ($entities === []) {
    echo "<div class='alert alert-info'>" . __('No company is visible to your profile.', 'sanamassets') . "</div>";
}

foreach ($entities as $row) {
    $id = (int) $row['id'];
    $brand = Brand::get($id);
    $display = (string) ($brand['display_name'] ?? $row['name']);
    $logo = Brand::logo($id);
    $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, (int) $row['level'] - 1));

    echo "<div class='card mb-2'><div class='card-body'>";
    echo "<div class='d-flex align-items-center gap-3 flex-wrap mb-2'>";
    echo "<div class='d-flex align-items-center justify-content-center border rounded bg-white' style='width:200px;height:64px'>";
    if ($logo !== null) {
        echo "<img src='" . Ui::e(Brand::logoUrl($id, $logo['mtime'])) . "' alt='' style='max-width:180px;max-height:52px'>";
    } else {
        echo "<span class='text-muted small'>" . __('No logo', 'sanamassets') . "</span>";
    }
    echo "</div>";
    echo "<div class='flex-grow-1'><div class='fw-semibold'>" . $indent . Ui::e($row['completename']) . "</div>";
    echo "<div class='text-muted small'>" . __('Display name', 'sanamassets') . ': ' . Ui::e($display) . "</div></div>";
    echo "</div>";

    if ($can_update) {
        echo "<form method='post' action='" . Ui::e(Ui::self()) . "' enctype='multipart/form-data' class='row g-2'>" . Ui::csrf();
        echo "<input type='hidden' name='action' value='save'>";
        echo "<input type='hidden' name='entities_id' value='$id'>";
        echo "<div class='col-md-5'><input class='form-control' name='display_name' maxlength='255' required value='" . Ui::e($display) . "'></div>";
        echo "<div class='col-md-5'><input class='form-control' type='file' name='logo' accept='image/png,image/jpeg,image/webp'></div>";
        echo "<div class='col-md-2'><button class='btn btn-primary w-100' type='submit'>" . __('Save', 'sanamassets') . "</button></div>";
        echo "</form>";
    }
    echo "</div></div>";
}

if ($can_create) {
    echo "<div class='card mt-3'><div class='card-header'><h3 class='card-title'>" . __('Add company', 'sanamassets') . "</h3></div><div class='card-body'>";
    echo "<form method='post' action='" . Ui::e(Ui::self()) . "'>" . Ui::csrf();
    echo "<input type='hidden' name='action' value='add'>";
    echo "<div class='mb-2'><label class='form-label'>" . __('Name', 'sanamassets') . "</label><input class='form-control' name='name' required maxlength='255'></div>";
    echo "<div class='mb-3'><label class='form-label'>" . __('Parent company', 'sanamassets') . "</label><select class='form-select' name='parent'>";
    foreach ($entities as $row) {
        $selected = (int) $row['id'] === 0 ? ' selected' : '';
        echo "<option value='" . (int) $row['id'] . "'$selected>" . Ui::e($row['completename']) . "</option>";
    }
    echo "</select></div>";
    echo "<button class='btn btn-primary' type='submit'>" . __('Add company', 'sanamassets') . "</button>";
    echo "</form></div></div>";
}

echo "</div>";
Html::footer();
