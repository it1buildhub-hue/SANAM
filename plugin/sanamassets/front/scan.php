<?php

/**
 * Mobile-first scan page.
 *
 * Opening this page (or scanning a label) only looks the asset up. It never verifies it,
 * never changes location, and never changes custody. Verification is a separate POST.
 */

include('../../../inc/includes.php');

global $DB;

use SanamPlugin\Sanamassets\Campaign;
use SanamPlugin\Sanamassets\Custody;
use SanamPlugin\Sanamassets\Discrepancy;
use SanamPlugin\Sanamassets\Items;
use SanamPlugin\Sanamassets\Label;
use SanamPlugin\Sanamassets\Schema;
use SanamPlugin\Sanamassets\Scan;
use SanamPlugin\Sanamassets\Ui;

Session::checkLoginUser();
if (!Session::haveRight(Schema::RIGHT_AUDIT, READ) && !Session::haveRight(Schema::RIGHT_MANAGE, READ)) {
    Session::checkRight(Schema::RIGHT_AUDIT, READ);
}
$can_act = Session::haveRight(Schema::RIGHT_AUDIT, UPDATE) || Session::haveRight(Schema::RIGHT_MANAGE, UPDATE);

$asset_id = strtoupper(trim((string) ($_REQUEST['asset'] ?? '')));
$query    = trim((string) ($_REQUEST['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = Items::byAssetId($asset_id);
    if ($identity === null || !$can_act) {
        Session::addMessageAfterRedirect(__('This asset cannot be changed from here.', 'sanamassets'), false, ERROR);
        Html::redirect(Ui::self() . '?q=' . urlencode($asset_id));
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'verify') {
        // Option value is "campaignId|areaId", so an area can never be paired with the wrong campaign.
        [$campaign_id, $area_id] = array_map('intval', explode('|', (string) ($_POST['target'] ?? '|')) + [0, 0]);
        $area = null;
        foreach (Campaign::areas($campaign_id) as $a) {
            if ((int) $a['id'] === $area_id) {
                $area = $a;
            }
        }
        try {
            $result = Campaign::verify(
                $campaign_id,
                $asset_id,
                $area_id,
                (string) ($_POST['method'] ?? 'manual') === 'qr' ? 'qr' : 'manual',
                (int) ($area['locations_id'] ?? 0),
                (int) ($_POST['observed_user'] ?? 0),
                (string) ($_POST['state_observed'] ?? ''),
                trim((string) ($_POST['comment'] ?? '')),
            );
            Session::addMessageAfterRedirect($result['message'], false, $result['result'] === 'verified' ? INFO : WARNING);
        } catch (\Throwable $e) {
            Session::addMessageAfterRedirect($e->getMessage(), false, ERROR);
        }
    } elseif ($action === 'discrepancy') {
        $type = (string) ($_POST['type'] ?? '');
        $campaign_id = (int) ($_POST['campaign_id'] ?? 0);
        Ui::run(static function () use ($campaign_id, $type, $identity, $asset_id): void {
            Discrepancy::open($campaign_id, $type, $identity['itemtype'], $identity['items_id'], $asset_id, trim((string) ($_POST['details'] ?? '')));
        }, __('Discrepancy recorded for review.', 'sanamassets'));
    } elseif ($action === 'issue_quick' || $action === 'return_quick') {
        // Custody changes need the asset-manager right, not the audit right.
        if (!Session::haveRight(Schema::RIGHT_MANAGE, UPDATE)) {
            Session::addMessageAfterRedirect(__('You cannot change custody.', 'sanamassets'), false, ERROR);
        } elseif ($action === 'issue_quick') {
            $kind = (string) ($_POST['kind'] ?? 'permanent');
            $users_id = (int) ($_POST['users_id'] ?? 0);
            $expected = (string) ($_POST['expected_return'] ?? '');
            $condition = (string) ($_POST['condition'] ?? '');
            Ui::run(static fn() => Custody::issue($identity['itemtype'], $identity['items_id'], [
                'kind'            => $kind,
                'users_id'        => $users_id,
                'expected_return' => $expected,
                'condition'       => $condition,
                'note'            => 'Issued from the scan page',
            ]), __('Asset issued.', 'sanamassets'));
        } else {
            $assignment_id = (int) ($_POST['assignment_id'] ?? 0);
            $condition = (string) ($_POST['condition'] ?? '');
            Ui::run(static fn() => Custody::returnAssignment($assignment_id, $condition, '', 'Returned from the scan page'), __('Asset returned.', 'sanamassets'));
        }
    }

    Html::redirect(Ui::self() . '?asset=' . urlencode($asset_id));
}

Html::header(__('Scan asset', 'sanamassets'), Ui::self(), 'assets', 'sanamassets');

echo "<div class='container-fluid py-2' style='max-width:640px'>";

// Search box with camera. Camera scanning needs a secure context (HTTPS) on phones.
echo "<form method='get' class='mb-3'>";
echo "<div class='input-group input-group-lg'>";
echo "<input class='form-control' name='q' value='" . Ui::e($query) . "' placeholder='" . Ui::e(__('Scan, or type ID, serial or name', 'sanamassets')) . "' autocomplete='off' autofocus>";
echo "<button class='btn btn-primary' type='submit'>" . __('Find', 'sanamassets') . "</button>";
echo "<button class='btn btn-outline-secondary' type='button' id='sanam-camera'>" . __('Camera', 'sanamassets') . "</button>";
echo "</div>";
echo "<div id='sanam-camera-msg' class='form-hint mt-1'></div>";
echo "</form>";

if ($asset_id === '' && $query !== '') {
    $hits = Scan::search($query);
    if (count($hits) === 1) {
        Html::redirect(Ui::self() . '?asset=' . urlencode($hits[0]['asset_id']));
    }
    echo "<div class='card'><div class='list-group list-group-flush'>";
    if ($hits === []) {
        echo "<div class='list-group-item text-muted'>" . __('No asset found.', 'sanamassets') . "</div>";
        if ($can_act) {
            echo "<a class='list-group-item list-group-item-action text-primary' href='/plugins/sanamassets/front/register.php?serial=" . urlencode($query) . "'>" . __('Register a new draft asset', 'sanamassets') . "</a>";
        }
    }
    foreach ($hits as $hit) {
        echo "<a class='list-group-item list-group-item-action' href='" . Ui::e(Ui::self() . '?asset=' . urlencode($hit['asset_id'])) . "'>";
        echo "<strong>" . Ui::e($hit['asset_id']) . "</strong> " . Ui::e($hit['name']) . " <span class='text-muted'>" . Ui::e($hit['serial']) . "</span></a>";
    }
    echo "</div></div>";
} elseif ($asset_id !== '') {
    $identity = Items::byAssetId($asset_id);
    if ($identity === null) {
        echo "<div class='alert alert-danger'>" . __('Unknown asset identity. Check the label or search by serial.', 'sanamassets') . "</div>";
    } elseif (!Session::haveAccessToEntity((int) $identity['item']->fields['entities_id'])) {
        echo "<div class='alert alert-danger'>" . __('You do not have access to this asset.', 'sanamassets') . "</div>";
    } else {
        $item = $identity['item'];
        $f = $item->fields;

        // A successful QR scan is the "scan verified" label event. It is not a verification.
        if (($_REQUEST['m'] ?? '') === 'qr' && Label::status($asset_id) !== 'scan_verified') {
            Label::record($asset_id, 'scanned', 'QR scan');
        }
        Scan::record(null, $asset_id, ($_REQUEST['m'] ?? '') === 'qr' ? 'qr' : 'manual', 'found');

        $holder = $f['users_id'] ? getUserName((int) $f['users_id']) : '';
        $location = $f['locations_id'] ? Dropdown::getDropdownName('sanam_locations', (int) $f['locations_id']) : '';
        $state = $f['states_id'] ? Dropdown::getDropdownName('sanam_states', (int) $f['states_id']) : '';

        echo "<div class='card mb-3'><div class='card-body'>";
        echo "<div class='text-muted small'>" . Ui::e($asset_id) . " · " . Ui::e($identity['itemtype']) . "</div>";
        echo "<h2 class='mb-2'>" . Ui::e($f['name']) . "</h2>";
        echo Ui::field(__('Status', 'sanamassets'), $state);
        echo Ui::field(__('Location', 'sanamassets'), $location);
        echo Ui::field(__('Custodian', 'sanamassets'), $holder);
        echo Ui::field(__('Serial', 'sanamassets'), $f['serial'] ?? '');
        echo Ui::field(__('Company', 'sanamassets'), Dropdown::getDropdownName('sanam_entities', (int) $f['entities_id']));
        echo Ui::field(__('Label', 'sanamassets'), Label::status($asset_id));
        echo "<div class='d-grid gap-2 mt-3'>";
        echo "<a class='btn btn-outline-primary' href='" . Ui::e($item->getFormURLWithID($identity['items_id'])) . "'>" . __('View full record', 'sanamassets') . "</a>";
        echo "<a class='btn btn-outline-secondary' href='/plugins/sanamassets/front/custody.php?asset=" . urlencode($asset_id) . "'>" . __('Assign or return', 'sanamassets') . "</a>";
        echo "<a class='btn btn-outline-secondary' href='/front/ticket.form.php?_itemtype=" . urlencode($identity['itemtype']) . "&_items_id=" . (int) $identity['items_id'] . "'>" . __('Open maintenance ticket', 'sanamassets') . "</a>";
        echo "</div></div></div>";

        if ($can_act) {
            $open = [];
            foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_campaigns', 'WHERE' => ['status' => 'active'], 'ORDER' => ['name ASC']]) as $c) {
                $open[] = $c;
            }

            if ($open !== []) {
                echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Verify in campaign', 'sanamassets') . "</h3></div><div class='card-body'>";
                echo "<form method='post'>" . Ui::csrf();
                echo "<input type='hidden' name='action' value='verify'><input type='hidden' name='asset' value='" . Ui::e($asset_id) . "'>";
                echo "<input type='hidden' name='method' value='" . (($_REQUEST['m'] ?? '') === 'qr' ? 'qr' : 'manual') . "'>";
                echo "<div class='mb-2'><label class='form-label'>" . __('Campaign and area', 'sanamassets') . "</label><select class='form-select' name='target'>";
                foreach ($open as $c) {
                    echo "<optgroup label='" . Ui::e($c['name']) . "'>";
                    foreach (Campaign::areas((int) $c['id']) as $a) {
                        if ($a['status'] === 'inaccessible') {
                            continue;
                        }
                        echo "<option value='" . (int) $c['id'] . "|" . (int) $a['id'] . "'>" . Ui::e($a['name']) . "</option>";
                    }
                    echo "</optgroup>";
                }
                echo "</select></div>";
                echo "<div class='mb-2'><label class='form-label'>" . __('Custodian confirmed as', 'sanamassets') . "</label>";
                echo User::dropdown(['name' => 'observed_user', 'value' => 0, 'display_emptychoice' => true, 'entity' => (int) $f['entities_id'], 'right' => 'all', 'display' => false]);
                echo "</div>";
                echo "<div class='mb-2'><label class='form-label'>" . __('Comment', 'sanamassets') . "</label><input class='form-control' name='comment' maxlength='255'></div>";
                echo "<button class='btn btn-success btn-lg w-100' type='submit'>" . __('Verify physically present', 'sanamassets') . "</button>";
                echo "</form></div></div>";
            }

            echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Report a problem', 'sanamassets') . "</h3></div><div class='card-body'>";
            echo "<form method='post'>" . Ui::csrf();
            echo "<input type='hidden' name='action' value='discrepancy'><input type='hidden' name='asset' value='" . Ui::e($asset_id) . "'>";
            echo "<div class='mb-2'><select class='form-select' name='type'>";
            foreach (['damage' => 'Damaged', 'label' => 'Label missing or unreadable', 'location' => 'Wrong location', 'custody' => 'Wrong custodian'] as $key => $text) {
                echo "<option value='" . Ui::e($key) . "'>" . Ui::e($text) . "</option>";
            }
            echo "</select></div>";
            echo "<div class='mb-2'><select class='form-select' name='campaign_id'>";
            foreach ($open as $c) {
                echo "<option value='" . (int) $c['id'] . "'>" . Ui::e($c['name']) . "</option>";
            }
            echo "</select></div>";
            // Discrepancy form only needs the campaign; the verify form above carries campaign + area.
            echo "<div class='mb-2'><input class='form-control' name='details' maxlength='500' placeholder='" . Ui::e(__('What did you see?', 'sanamassets')) . "'></div>";
            echo "<button class='btn btn-outline-danger w-100' type='submit'" . ($open === [] ? ' disabled' : '') . ">" . __('Record for review', 'sanamassets') . "</button>";
            echo "</form></div></div>";
        }

        if (Session::haveRight(Schema::RIGHT_MANAGE, UPDATE)) {
            $holder_row = null;
            foreach ($DB->request(['FROM' => 'sanam_plugin_sanamassets_assignments', 'WHERE' => ['itemtype' => $identity['itemtype'], 'items_id' => $identity['items_id'], 'status' => 'active', 'kind' => ['<>', 'shared']], 'LIMIT' => 1]) as $row) {
                $holder_row = $row;
            }
            $conditions = ['Good', 'Fair', 'Damaged', 'Not working', 'New'];
            echo "<div class='card mb-3'><div class='card-header'><h3 class='card-title'>" . __('Custody', 'sanamassets') . "</h3></div><div class='card-body'>";
            if ($holder_row !== null) {
                echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='return_quick'><input type='hidden' name='asset' value='" . Ui::e($asset_id) . "'>";
                echo "<input type='hidden' name='assignment_id' value='" . (int) $holder_row['id'] . "'>";
                echo "<div class='mb-2'><select class='form-select' name='condition'>";
                foreach ($conditions as $c) {
                    echo "<option>" . Ui::e($c) . "</option>";
                }
                echo "</select></div>";
                echo "<button class='btn btn-outline-success w-100' type='submit'>" . __('Return from', 'sanamassets') . " " . Ui::e(getUserName((int) $holder_row['users_id'])) . "</button>";
                echo "</form>";
            } else {
                echo "<form method='post'>" . Ui::csrf() . "<input type='hidden' name='action' value='issue_quick'><input type='hidden' name='asset' value='" . Ui::e($asset_id) . "'>";
                echo "<div class='mb-2'>" . User::dropdown(['name' => 'users_id', 'value' => 0, 'display_emptychoice' => true, 'right' => 'all', 'display' => false]) . "</div>";
                echo "<div class='mb-2'><select class='form-select' name='kind'><option value='permanent'>" . __('Permanent', 'sanamassets') . "</option><option value='temporary'>" . __('Temporary', 'sanamassets') . "</option></select></div>";
                echo "<div class='mb-2'><input class='form-control' type='date' name='expected_return' aria-label='" . Ui::e(__('Expected return (temporary)', 'sanamassets')) . "'></div>";
                echo "<div class='mb-2'><select class='form-select' name='condition'>";
                foreach ($conditions as $c) {
                    echo "<option>" . Ui::e($c) . "</option>";
                }
                echo "</select></div>";
                echo "<button class='btn btn-outline-primary w-100' type='submit'>" . __('Issue to person', 'sanamassets') . "</button>";
                echo "</form>";
            }
            echo "</div></div>";
        }
    }
}

echo "</div>";
?>
<script>
(function () {
    var btn = document.getElementById('sanam-camera');
    var msg = document.getElementById('sanam-camera-msg');
    btn.addEventListener('click', async function () {
        if (!('BarcodeDetector' in window) || !window.isSecureContext) {
            msg.textContent = <?= json_encode(__('Camera scanning needs HTTPS on this device. Type the ID instead.', 'sanamassets')) ?>;
            return;
        }
        try {
            var detector = new BarcodeDetector({ formats: ['qr_code'] });
            var stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            var video = document.createElement('video');
            video.setAttribute('playsinline', ''); video.srcObject = stream; await video.play();
            var overlay = document.createElement('div');
            overlay.style.cssText = 'position:fixed;inset:0;background:#000;z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center';
            video.style.cssText = 'max-width:100%;max-height:80vh';
            overlay.appendChild(video);
            document.body.appendChild(overlay);
            var timer = setInterval(async function () {
                var codes = await detector.detect(video);
                if (codes.length) {
                    var url = new URL(codes[0].rawValue);
                    var asset = url.searchParams.get('asset');
                    if (asset) {
                        clearInterval(timer);
                        stream.getTracks().forEach(function (t) { t.stop(); });
                        window.location = <?= json_encode(Ui::self()) ?> + '?m=qr&asset=' + encodeURIComponent(asset);
                    }
                }
            }, 300);
        } catch (e) {
            msg.textContent = e.message;
        }
    });
})();
</script>
<?php
Html::footer();
