<?php

/**
 * Audit campaigns: list, create, and the campaign workspace (?id=...).
 */

include('../../../inc/includes.php');

global $DB;

use SanamPlugin\Sanamassets\Campaign;
use SanamPlugin\Sanamassets\Discrepancy;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
Session::checkRight(Schema::RIGHT_MANAGE, READ);
$can_manage = Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);
$can_audit  = Session::haveRight(Schema::RIGHT_AUDIT, UPDATE) || $can_manage;

$id = (int) ($_REQUEST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $back = Ui::self() . ($id ? '?id=' . $id : '');

    switch ($action) {
        case 'create':
            if ($can_manage) {
                Ui::run(static fn() => Campaign::create(trim((string) $_POST['name']), (int) $_POST['entities_id'], (string) $_POST['kind']), __('Campaign created.', 'sanamassets'));
            }
            break;
        case 'add_area':
            if ($can_manage || $can_audit) {
                Ui::run(static fn() => Campaign::addArea($id, trim((string) $_POST['name']), (int) ($_POST['locations_id'] ?? 0)), __('Area added.', 'sanamassets'));
            }
            break;
        case 'start':
            if ($can_manage) {
                Ui::run(static fn() => Campaign::start($id), __('Campaign started. Expected records are frozen.', 'sanamassets'));
            }
            break;
        case 'area_status':
            if ($can_audit) {
                Ui::run(static fn() => Campaign::setAreaStatus($id, (int) $_POST['area_id'], (string) $_POST['status'], trim((string) ($_POST['reason'] ?? ''))), __('Area updated.', 'sanamassets'));
            }
            break;
        case 'to_review':
            if ($can_manage) {
                Ui::run(static fn() => Campaign::moveToReview($id), __('Moved to review. Unverified items are listed as not found.', 'sanamassets'));
            }
            break;
        case 'review':
            if ($can_manage) {
                Ui::run(static fn() => Discrepancy::review((int) $_POST['discrepancy_id'], (string) $_POST['decision']), __('Decision saved.', 'sanamassets'));
            }
            break;
        case 'close':
            if ($can_manage) {
                Ui::run(static fn() => Campaign::close($id), __('Campaign closed and locked.', 'sanamassets'));
            }
            break;
        case 'reopen':
            if ($can_manage) {
                Ui::run(static fn() => Campaign::reopen($id, trim((string) $_POST['reason'])), __('Campaign reopened. The reason is recorded.', 'sanamassets'));
            }
            break;
    }
    Html::redirect($back);
}

Html::header(__('Audit campaigns', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');
echo "<div class='container-fluid py-2'>";

if ($id === 0) {
    // List and create.
    echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Audit campaigns', 'sanamassets') . "</h3></div>";
    echo "<div class='table-responsive'><table class='table card-table'><thead><tr><th>" . __('Name', 'sanamassets') . "</th><th>" . __('Type', 'sanamassets') . "</th><th>" . __('Status', 'sanamassets') . "</th><th>" . __('Company', 'sanamassets') . "</th></tr></thead><tbody>";
    foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_campaigns', 'ORDER' => ['id DESC']]) as $c) {
        echo "<tr><td><a href='?id=" . (int) $c['id'] . "'>" . Ui::e($c['name']) . "</a></td><td>" . Ui::e($c['kind']) . "</td><td><span class='badge'>" . Ui::e($c['status']) . "</span></td><td>" . Ui::e(Dropdown::getDropdownName('sanam_entities', (int) $c['entities_id'])) . "</td></tr>";
    }
    echo "</tbody></table></div></div>";

    if ($can_manage) {
        echo "<div class='card'><div class='card-body'><form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='create'>";
        echo "<div class='row g-2'>";
        echo "<div class='col-md-4'><input class='form-control' name='name' required placeholder='" . Ui::e(__('Campaign name, e.g. Baseline 2026 - Buildhub HQ', 'sanamassets')) . "'></div>";
        echo "<div class='col-md-3'>" . Entity::dropdown(['name' => 'entities_id', 'value' => $_SESSION['sanamactive_entity'], 'display' => false]) . "</div>";
        echo "<div class='col-md-3'><select class='form-select' name='kind'><option value='baseline'>" . __('Baseline (first full count)', 'sanamassets') . "</option><option value='periodic'>" . __('Periodic audit', 'sanamassets') . "</option><option value='spot'>" . __('Spot check', 'sanamassets') . "</option></select></div>";
        echo "<div class='col-md-2'><button class='btn btn-primary w-100'>" . __('Create', 'sanamassets') . "</button></div>";
        echo "</div></form></div></div>";
    }
} else {
    $campaign = Campaign::get($id);
    if ($campaign === null) {
        echo "<div class='alert alert-danger'>" . __('Campaign not found.', 'sanamassets') . "</div>";
    } else {
        $coverage = Campaign::coverage($id);
        $areas = Campaign::areas($id);
        $status = $campaign['status'];
        $pct = $coverage['expected'] > 0 ? (int) round(100 * $coverage['verified'] / $coverage['expected']) : 0;

        echo "<div class='card mb-3'><div class='card-body'>";
        echo "<div class='d-flex justify-content-between flex-wrap gap-2'><div><h2 class='mb-1'>" . Ui::e($campaign['name']) . "</h2>";
        echo "<span class='badge bg-blue-lt'>" . Ui::e($status) . "</span> <span class='text-muted small'>" . Ui::e($campaign['kind']) . " · " . Ui::e(Dropdown::getDropdownName('sanam_entities', (int) $campaign['entities_id'])) . "</span></div>";
        echo "<a class='btn btn-outline-secondary' href='" . Ui::e(Ui::self()) . "'>" . __('All campaigns', 'sanamassets') . "</a></div>";

        echo "<div class='row mt-3 g-2'>";
        echo "<div class='col-md-4'><div class='card'><div class='card-body'><div class='text-muted'>" . __('Verified', 'sanamassets') . "</div><div class='h2 mb-0'>" . $coverage['verified'] . " / " . $coverage['expected'] . "</div><div class='small text-muted'>" . $pct . "%</div></div></div></div>";
        echo "<div class='col-md-4'><div class='card'><div class='card-body'><div class='text-muted'>" . __('Areas not finished', 'sanamassets') . "</div><div class='h2 mb-0'>" . count($coverage['not_inspected_areas']) . "</div></div></div></div>";
        echo "<div class='col-md-4'><div class='card'><div class='card-body'><div class='text-muted'>" . __('Open findings', 'sanamassets') . "</div><div class='h2 mb-0'>" . $coverage['open_discrepancies'] . "</div></div></div></div>";
        echo "</div>";
        echo "<p class='small text-muted mt-2 mb-0'>" . __('Expected = items registered when the campaign started. Items registered later show up only when found, as unexpected.', 'sanamassets') . "</p>";
        echo "</div></div>";

        // Areas and coverage.
        echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Areas and coverage', 'sanamassets') . "</h3></div><div class='table-responsive'><table class='table card-table'><thead><tr><th>" . __('Area', 'sanamassets') . "</th><th>" . __('Status', 'sanamassets') . "</th><th>" . __('Reason', 'sanamassets') . "</th>" . ($can_audit ? "<th></th>" : "") . "</tr></thead><tbody>";
        foreach ($areas as $a) {
            echo "<tr><td>" . Ui::e($a['name']) . "</td><td>" . Ui::e($a['status']) . "</td><td>" . Ui::e($a['inaccessible_reason'] ?? '') . "</td>";
            if ($can_audit && in_array($status, ['active', 'review'], true)) {
                echo "<td><form method='post' class='d-flex gap-1'>" . Ui::csrf();
                echo "<input type='hidden' name='action' value='area_status'><input type='hidden' name='area_id' value='" . (int) $a['id'] . "'>";
                echo "<select class='form-select form-select-sm' name='status'><option value='in_progress'>" . __('In progress', 'sanamassets') . "</option><option value='done'>" . __('Done', 'sanamassets') . "</option><option value='inaccessible'>" . __('Cannot inspect', 'sanamassets') . "</option></select>";
                echo "<input class='form-control form-control-sm' name='reason' placeholder='" . Ui::e(__('Reason if cannot inspect', 'sanamassets')) . "'>";
                echo "<button class='btn btn-sm btn-outline-primary'>" . __('Save', 'sanamassets') . "</button></form></td>";
            } elseif ($can_audit) {
                echo "<td></td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table></div>";
        if ($can_manage && $status === 'draft') {
            echo "<div class='card-body'><form method='post' class='d-flex gap-2'>" . Ui::csrf() . "<input type='hidden' name='action' value='add_area'>";
            echo "<input class='form-control' name='name' required placeholder='" . Ui::e(__('Room or area name', 'sanamassets')) . "'>";
            echo "<button class='btn btn-outline-primary'>" . __('Add area', 'sanamassets') . "</button></form></div>";
        }
        echo "</div>";

        // Findings to review.
        $findings = Discrepancy::forCampaign($id);
        if ($findings !== []) {
            echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Findings', 'sanamassets') . "</h3></div><div class='table-responsive'><table class='table card-table'><thead><tr><th>" . __('Type', 'sanamassets') . "</th><th>" . __('Asset', 'sanamassets') . "</th><th>" . __('Details', 'sanamassets') . "</th><th>" . __('Decision', 'sanamassets') . "</th></tr></thead><tbody>";
            foreach ($findings as $d) {
                echo "<tr><td>" . Ui::e($d['type']) . "</td><td>" . Ui::e($d['asset_id'] ?? '') . "</td><td class='small'>" . Ui::e($d['details'] ?? '') . "</td><td>";
                if ($d['status'] === 'open' && $can_manage && $status === 'review') {
                    echo "<form method='post' class='d-flex gap-1'>" . Ui::csrf() . "<input type='hidden' name='action' value='review'><input type='hidden' name='discrepancy_id' value='" . (int) $d['id'] . "'>";
                    echo "<button name='decision' value='approved' class='btn btn-sm btn-outline-success'>" . __('Accept', 'sanamassets') . "</button>";
                    echo "<button name='decision' value='rejected' class='btn btn-sm btn-outline-secondary'>" . __('Reject', 'sanamassets') . "</button></form>";
                } else {
                    echo Ui::e($d['status']);
                }
                echo "</td></tr>";
            }
            echo "</tbody></table></div></div>";
        }

        // Lifecycle actions.
        echo "<div class='card'><div class='card-body d-flex flex-wrap gap-2'>";
        if ($can_manage && $status === 'draft') {
            echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='start'><button class='btn btn-primary'>" . __('Start campaign', 'sanamassets') . "</button></form>";
        }
        if ($can_manage && $status === 'active') {
            echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='to_review'><button class='btn btn-outline-primary'>" . __('Move to review', 'sanamassets') . "</button></form>";
        }
        if ($can_manage && $status === 'review') {
            echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='close'><button class='btn btn-success'>" . __('Close and lock', 'sanamassets') . "</button></form>";
        }
        if ($can_manage && $status === 'closed') {
            echo "<form method='post' class='d-flex gap-2'>" . Ui::csrf() . "<input type='hidden' name='action' value='reopen'>";
            echo "<input class='form-control' name='reason' required placeholder='" . Ui::e(__('Reason for reopening', 'sanamassets')) . "'>";
            echo "<button class='btn btn-outline-danger'>" . __('Reopen', 'sanamassets') . "</button></form>";
        }
        echo "</div></div>";
    }
}

echo "</div>";
Html::footer();
