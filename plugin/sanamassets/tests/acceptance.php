<?php

/**
 * Acceptance checks for the SANAM Assets plugin (plan section 15).
 *
 * Runs ONLY against the test instance, never staging:
 *   set SANAM_CONFIG_DIR=D:\Learn\sanam-test\config
 *   set SANAM_VAR_DIR=D:\Learn\sanam-test\files
 *   php D:\Learn\sanamassets\tests\acceptance.php
 */

$sanam_root = 'D:/Learn/sanam-core';
if (!getenv('SANAM_CONFIG_DIR') || !str_contains((string) getenv('SANAM_CONFIG_DIR'), 'sanam-test')) {
    fwrite(STDERR, "Refusing to run: SANAM_CONFIG_DIR must contain sanam-test.\n");
    exit(2);
}

// Same bootstrap SANAM's own test suite uses (tests/bootstrap.php in the core).
require_once $sanam_root . '/vendor/autoload.php';
global $kernel;
$kernel = new \Sanam\Kernel\Kernel();
$kernel->boot();
global $DB;
if (!isset($DB)) {
    $DB = new DB();
}

use SanamPlugin\Sanamassets\AssetId;
use SanamPlugin\Sanamassets\Brand;
use SanamPlugin\Sanamassets\Campaign;
use SanamPlugin\Sanamassets\Conflict;
use SanamPlugin\Sanamassets\Custody;
use SanamPlugin\Sanamassets\Dashboard;
use SanamPlugin\Sanamassets\Discrepancy;
use SanamPlugin\Sanamassets\Items;
use SanamPlugin\Sanamassets\Label;
use SanamPlugin\Sanamassets\Report;
use SanamPlugin\Sanamassets\Scan;

$_SESSION['sanamID'] = 2;
$_SESSION['sanamname'] = 'sanam';
$_SESSION['sanamactiveentities'] = [0];
$_SESSION['sanamactive_entity'] = 0;
$_SESSION['sanamactiveprofile'] = ['id' => 4];
// Maintenance mode, as in create_accounts.php: without it the core's rights filter silently refuses
// entity inserts and updates, which made the company checks fail for the wrong reason.
$_SESSION['sanamcronuserrunning'] = 'acceptance';

$pass = 0;
$fail = 0;

function ok(string $name, bool $cond, string $detail = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "PASS  $name\n";
    } else {
        $fail++;
        echo "FAIL  $name" . ($detail !== '' ? "  -- $detail" : '') . "\n";
    }
}

function throwsWith(callable $fn, string $needle): bool
{
    try {
        $fn();
        return false;
    } catch (\Throwable $e) {
        return str_contains($e->getMessage(), $needle);
    }
}

function newItem(string $class, array $input): int
{
    $item = new $class();
    $id = $item->add($input);
    return $id === false ? 0 : (int) $id;
}

function fieldOf(string $class, int $id, string $field): mixed
{
    $item = new $class();
    return $item->getFromDB($id) ? $item->fields[$field] : null;
}

function firstRow(string $table, array $where): ?array
{
    global $DB;
    foreach ($DB->request(['FROM' => $table, 'WHERE' => $where, 'LIMIT' => 1]) as $row) {
        return $row;
    }
    return null;
}

function countRows(string $table, array $where): int
{
    global $DB;
    foreach ($DB->request(['COUNT' => 'c', 'FROM' => $table, 'WHERE' => $where]) as $r) {
        return (int) $r['c'];
    }
    return 0;
}

$stamp = date('His');

// --- 1. Identity: generated, immutable, never reused ----------------------------
$pc1 = newItem(Computer::class, ['name' => "T$stamp-PC1", 'entities_id' => 0, 'serial' => "SN-$stamp-A", 'otherserial' => 'legacy-xyz']);
$pc2 = newItem(Computer::class, ['name' => "T$stamp-PC2", 'entities_id' => 0, 'serial' => "SN-$stamp-B"]);
$id1 = (string) fieldOf(Computer::class, $pc1, 'otherserial');
$id2 = (string) fieldOf(Computer::class, $pc2, 'otherserial');

ok('1a identity format SNM-000000', AssetId::isValid($id1) && AssetId::isValid($id2), "$id1 / $id2");
ok('1b identities increase in order', (int) substr($id2, 4) > (int) substr($id1, 4));
ok('1c typed otherserial kept as legacy reference', countRows('sanam_plugin_sanamassets_assetids', ['asset_id' => $id1, 'legacy_ref' => 'legacy-xyz']) === 1);

$pc1_update = new Computer();
$pc1_update->getFromDB($pc1);
$pc1_update->update(['id' => $pc1, 'otherserial' => 'SNM-999999']);
ok('1d identity cannot be changed', fieldOf(Computer::class, $pc1, 'otherserial') === $id1);

// --- 2. Duplicate serial is refused, not silently created ------------------------
$dup = newItem(Computer::class, ['name' => "T$stamp-DUP", 'entities_id' => 0, 'serial' => "SN-$stamp-A"]);
ok('2a second item with same serial not created', $dup === 0 && countRows('sanam_computers', ['serial' => "SN-$stamp-A", 'is_deleted' => 0]) === 1);

// --- 3. Missing serial still registers --------------------------------------------
$nosn = newItem(Computer::class, ['name' => "T$stamp-NOSN", 'entities_id' => 0]);
ok('3a missing serial registers with identity', $nosn > 0 && AssetId::isValid((string) fieldOf(Computer::class, $nosn, 'otherserial')));

// --- 4. Identifier conflict is flagged on change, never merged -------------------
$pc5 = newItem(Computer::class, ['name' => "T$stamp-PC5", 'entities_id' => 0, 'serial' => "SN-$stamp-C"]);
$pc5_item = new Computer();
$pc5_item->getFromDB($pc5);
$pc5_item->update(['id' => $pc5, 'serial' => "SN-$stamp-A"]);
ok('4a conflict recorded', countRows('sanam_plugin_sanamassets_conflicts', ['items_id' => $pc5, 'value' => "SN-$stamp-A"]) === 1);
ok('4b both items still exist', fieldOf(Computer::class, $pc1, 'name') !== null && fieldOf(Computer::class, $pc5, 'name') !== null);

// --- 5. Move keeps identity and history ----------------------------------------
$loc_a = newItem(Location::class, ['name' => "T$stamp-Room A", 'entities_id' => 0]);
$loc_b = newItem(Location::class, ['name' => "T$stamp-Room B", 'entities_id' => 0]);
$pc2_item = new Computer();
$pc2_item->getFromDB($pc2);
$pc2_item->update(['id' => $pc2, 'locations_id' => $loc_a]);
$pc2_item->getFromDB($pc2);
$pc2_item->update(['id' => $pc2, 'locations_id' => $loc_b]);
ok('5a move keeps the same identity', (string) fieldOf(Computer::class, $pc2, 'otherserial') === $id2);
ok('5b identity mapping still points to the item', Items::byAssetId($id2) !== null);

// --- 6. Purge retires the identity; the number is never reused -----------------
$del = newItem(Computer::class, ['name' => "T$stamp-DEL", 'entities_id' => 0]);
$del_id = (string) fieldOf(Computer::class, $del, 'otherserial');
$del_item = new Computer();
$del_item->getFromDB($del);
$del_item->delete(['id' => $del]);          // moved to trash
$del_item->delete(['id' => $del], true);     // purged
ok('6a purged identity is retired', countRows('sanam_plugin_sanamassets_assetids', ['asset_id' => $del_id, 'status' => 'retired']) === 1);
$after = newItem(Computer::class, ['name' => "T$stamp-AFTER", 'entities_id' => 0]);
ok('6b next item gets a new number', (string) fieldOf(Computer::class, $after, 'otherserial') !== $del_id);

// --- 7. Search and unknown identity -------------------------------------------
ok('7a unknown identity not found', Items::byAssetId('SNM-999998') === null);
$hits = Scan::search("SN-$stamp-B");
ok('7b search by serial finds the item', count($hits) === 1 && $hits[0]['asset_id'] === $id2, json_encode($hits));

// --- 8. Labels: never localhost, lifecycle, reprint keeps the ID --------------
Config::setConfigurationValues('plugin:sanamassets', ['base_url' => 'http://localhost']);
ok('8a localhost refused for labels', throwsWith(static fn() => Label::scanUrl($id1), 'localhost'));
Config::setConfigurationValues('plugin:sanamassets', ['base_url' => 'https://assets.test.lan']);
$url = Label::scanUrl($id1);
ok('8b label URL uses the company address', str_starts_with($url, 'https://assets.test.lan/plugins/sanamassets/front/scan.php?asset=' . $id1));
ok('8c QR is generated as SVG', str_contains(Label::qrSvg($url), '<svg'));
ok('8d new label is not printed', Label::status($id1) === 'not_printed');
Label::record($id1, 'printed', 'test');
ok('8e printed', Label::status($id1) === 'printed');
Label::record($id1, 'scanned', 'test');
ok('8f scan-verified', Label::status($id1) === 'scan_verified');
$ids_before = countRows('sanam_plugin_sanamassets_assetids', []);
Label::record($id1, 'reprinted', 'torn label');
ok('8g reprint creates no new identity', countRows('sanam_plugin_sanamassets_assetids', []) === $ids_before);

// --- 9. Audit campaign ------------------------------------------------------------
$expected_total = iterator_count(Items::activeIdentities());
$camp = Campaign::create("T$stamp campaign", 0, 'baseline');
Campaign::addArea($camp, 'Room A', $loc_a);
$area_b = Campaign::addArea($camp, 'Room B', $loc_b);
$area_a = (int) array_values(array_filter(Campaign::areas($camp), static fn($a) => $a['name'] === 'Room A'))[0]['id'];

$started = Campaign::start($camp);
ok('9a start freezes the expected records', $started === $expected_total, "started=$started expected=$expected_total");
ok('9b campaign active', Campaign::get($camp)['status'] === 'active');

$r1 = Campaign::verify($camp, $id1, $area_a, 'qr', 0, 0, '', '');
ok('9c first verification recorded', $r1['result'] === 'verified', $r1['result']);
$r2 = Campaign::verify($camp, $id1, $area_a, 'qr', 0, 0, '', '');
ok('9d second scan is a duplicate, not a second record', $r2['result'] === 'duplicate' && countRows('sanam_plugin_sanamassets_verifications', ['campaigns_id' => $camp, 'asset_id' => $id1]) === 1);
$r3 = Campaign::verify($camp, 'SNM-999997', $area_a, 'manual', 0, 0, '', '');
ok('9e unknown code recorded as unknown', $r3['result'] === 'unknown');

$unexpected = newItem(Computer::class, ['name' => "T$stamp-LATE", 'entities_id' => 0]);
$late_id = (string) fieldOf(Computer::class, $unexpected, 'otherserial');
$r4 = Campaign::verify($camp, $late_id, $area_a, 'qr', 0, 0, '', '');
ok('9f item not in the snapshot is unexpected, not added silently', $r4['result'] === 'unexpected' && countRows('sanam_plugin_sanamassets_expected', ['campaigns_id' => $camp, 'asset_id' => $late_id]) === 0);

ok('9g coverage lists the room not inspected', in_array('Room B', Campaign::coverage($camp)['not_inspected_areas'], true));
$pc5_before = [fieldOf(Computer::class, $pc5, 'states_id'), fieldOf(Computer::class, $pc5, 'users_id'), fieldOf(Computer::class, $pc5, 'locations_id')];
ok('9h close refused while a room is open', throwsWith(static function () use ($camp) {
    Campaign::moveToReview($camp);
    Campaign::close($camp);
}, 'Areas not finished'));
$pc5_after = [fieldOf(Computer::class, $pc5, 'states_id'), fieldOf(Computer::class, $pc5, 'users_id'), fieldOf(Computer::class, $pc5, 'locations_id')];
ok('9h2 not-found item keeps its status, custodian and location', $pc5_before === $pc5_after);
ok('9h3 not-found item has a finding, not a lost status', countRows('sanam_plugin_sanamassets_discrepancies', ['campaigns_id' => $camp, 'type' => 'not_found', 'items_id' => $pc5]) === 1);

// --- "Not found" is a finding, not a lost status ------------------------------
$not_found = countRows('sanam_plugin_sanamassets_discrepancies', ['campaigns_id' => $camp, 'type' => 'not_found']);
ok('9i unverified items become not_found findings', $not_found === $expected_total - 1, "findings=$not_found");

// Finish and review everything, then close.
Campaign::setAreaStatus($camp, $area_b, 'done');
Campaign::setAreaStatus($camp, $area_a, 'done');
foreach (Campaign::areas($camp) as $a) {
    ok('9k areas done: ' . $a['name'], $a['status'] === 'done');
}
ok('9l close refused while findings are unreviewed', throwsWith(static fn() => Campaign::close($camp), 'need a decision'));
foreach (Discrepancy::forCampaign($camp) as $d) {
    Discrepancy::review((int) $d['id'], 'approved');
}
Campaign::close($camp);
ok('9m campaign closed and locked', Campaign::get($camp)['status'] === 'closed');
ok('9n closed campaign refuses new verification', throwsWith(static fn() => Campaign::verify($camp, $id2, $area_a, 'manual', 0, 0, '', ''), 'not open'));
ok('9o reopen needs a reason', throwsWith(static fn() => Campaign::reopen($camp, ''), 'reason'));
Campaign::reopen($camp, 'Missed a room');
ok('9p reopen is logged and counted', (int) Campaign::get($camp)['reopen_count'] === 1 && countRows('sanam_plugin_sanamassets_campaignlogs', ['campaigns_id' => $camp, 'action' => 'reopened']) === 1);
Campaign::moveToReview($camp);
foreach (Discrepancy::forCampaign($camp) as $d) {
    if ($d['status'] === 'open') {
        Discrepancy::review((int) $d['id'], 'approved');
    }
}
Campaign::close($camp);

// --- 10. Custody ----------------------------------------------------------------
$user_a = newItem(User::class, ['name' => "t.a.$stamp", 'password' => 'Test-Pass-2026!x', 'password2' => 'Test-Pass-2026!x', 'entities_id' => 0, 'is_active' => 1]);
$user_b = newItem(User::class, ['name' => "t.b.$stamp", 'password' => 'Test-Pass-2026!x', 'password2' => 'Test-Pass-2026!x', 'entities_id' => 0, 'is_active' => 1]);
ok('10a test users exist', $user_a > 0 && $user_b > 0);

$pc_c = newItem(Computer::class, ['name' => "T$stamp-CUST", 'entities_id' => 0]);
$asg_a = Custody::issue(Computer::class, $pc_c, ['kind' => 'permanent', 'users_id' => $user_a, 'condition' => 'Good', 'accessories' => 'charger']);
ok('10b permanent issue sets the native custodian', (int) fieldOf(Computer::class, $pc_c, 'users_id') === $user_a);
ok('10c second holder refused', throwsWith(static fn() => Custody::issue(Computer::class, $pc_c, ['kind' => 'permanent', 'users_id' => $user_b]), 'already has an active holder'));
ok('10d temporary needs a future return date', throwsWith(static fn() => Custody::issue(Computer::class, $pc_c, ['kind' => 'temporary', 'users_id' => $user_b, 'expected_return' => '2000-01-01']), 'future'));
ok('10e clearance refused while assets are outstanding', throwsWith(static fn() => Custody::clear($user_a, ''), 'outstanding'));

$shared1 = Custody::issue(Computer::class, $pc_c, ['kind' => 'shared', 'users_id' => $user_b]);
ok('10f shared assets may have several responsible people', $shared1 > 0);

Custody::returnAssignment($asg_a, 'Good', 'charger', 'returned');
ok('10g return clears the native custodian when nobody else holds it', (int) fieldOf(Computer::class, $pc_c, 'users_id') === 0);

// Transfer uses a permanent issue on a fresh item.
$pc_t = newItem(Computer::class, ['name' => "T$stamp-XFER", 'entities_id' => 0]);
$asg_t = Custody::issue(Computer::class, $pc_t, ['kind' => 'permanent', 'users_id' => $user_a]);
$new_t = Custody::transfer($asg_t, $user_b, 'Good', 'handover');
ok('10h transfer moves the holder and keeps one active holder', (int) fieldOf(Computer::class, $pc_t, 'users_id') === $user_b && countRows('sanam_plugin_sanamassets_assignments', ['items_id' => $pc_t, 'status' => 'active', 'kind' => 'permanent']) === 1);
ok('10i transfer leaves a returned record for the previous holder', countRows('sanam_plugin_sanamassets_assignments', ['id' => $asg_t, 'status' => 'returned']) === 1);

ok('10j clearance refused while holding an asset', throwsWith(static fn() => Custody::clear($user_b, ''), 'outstanding'));

// Closed audit results do not change after a later transfer of the same asset.
$before = json_encode(countRows('sanam_plugin_sanamassets_verifications', ['campaigns_id' => $camp, 'asset_id' => $id1]));
$v_before = json_encode(firstRow('sanam_plugin_sanamassets_verifications', ['campaigns_id' => $camp, 'asset_id' => $id1]));
$holder = Custody::issue(Computer::class, $pc1, ['kind' => 'permanent', 'users_id' => $user_a]);
Custody::transfer($holder, $user_b, 'Good', 'after audit');
$v_after = json_encode(firstRow('sanam_plugin_sanamassets_verifications', ['campaigns_id' => $camp, 'asset_id' => $id1]));
ok('10k closed audit record unchanged after later transfer', $v_before === $v_after && $v_before !== 'null', $v_before);

// --- 11. Dashboard and reports -----------------------------------------------------
$blocks = Dashboard::compute();
ok('11a dashboard computes every block', count($blocks) === 9, (string) count($blocks));
[$head, $rows] = Report::inventory();
ok('11b inventory export has rows with identities', count($head) === 10 && count($rows) >= $expected_total);

// --- 12. Custom asset types (cameras, displays, media, kits) ----------------------------
$cctv = null;
foreach (\Sanam\Asset\AssetDefinitionManager::getInstance()->getCustomObjectClassNames() as $class) {
    if (str_ends_with($class, 'CCTVCameraAsset')) {
        $cctv = $class;
    }
}
ok('12a CCTV custom type is defined', $cctv !== null);
if ($cctv !== null) {
    $cam = newItem($cctv, ['name' => "T$stamp-CAM1", 'entities_id' => 0, 'serial' => "CAM-$stamp"]);
    $cam_id = $cam > 0 ? (string) fieldOf($cctv, $cam, 'otherserial') : '';
    ok('12b custom asset gets a SANAM identity', AssetId::isValid($cam_id), $cam_id);
    $cam2 = newItem($cctv, ['name' => "T$stamp-CAM2", 'entities_id' => 0, 'serial' => "CAM-$stamp"]);
    ok('12c duplicate camera serial refused', $cam2 === 0);
    ok('12d only one camera row carries that serial', countRows('sanam_assets_assets', ['serial' => "CAM-$stamp"]) === 1);
}

// --- 13. SIM replacement keeps the line and its history (native SANAM behaviour) --------
$line = newItem(Line::class, ['name' => "T$stamp-LINE", 'caller_num' => '+971500000001', 'entities_id' => 0]);
$phone = newItem(Phone::class, ['name' => "T$stamp-SIMPHONE", 'entities_id' => 0]);
$sim_model = newItem(DeviceSimcard::class, ['designation' => "T$stamp-SIM", 'entities_id' => 0]);
$retired = (int) (firstRow('sanam_states', ['name' => 'Retired'])['id'] ?? 0);
$sim_old = newItem(Item_DeviceSimcard::class, ['itemtype' => Phone::class, 'items_id' => $phone, 'devicesimcards_id' => $sim_model, 'serial' => "ICCID-OLD-$stamp", 'lines_id' => $line, 'entities_id' => 0]);
ok('13a first SIM is on the line', $sim_old > 0 && (int) fieldOf(Item_DeviceSimcard::class, $sim_old, 'lines_id') === $line);

$sim_new = newItem(Item_DeviceSimcard::class, ['itemtype' => Phone::class, 'items_id' => $phone, 'devicesimcards_id' => $sim_model, 'serial' => "ICCID-NEW-$stamp", 'lines_id' => $line, 'entities_id' => 0]);
$old_item = new Item_DeviceSimcard();
$old_item->getFromDB($sim_old);
$old_item->update(['id' => $sim_old, 'states_id' => $retired]);

ok('13b line is the same record and number', fieldOf(Line::class, $line, 'caller_num') === '+971500000001');
ok('13c replacement SIM is on the same line', $sim_new > 0 && (int) fieldOf(Item_DeviceSimcard::class, $sim_new, 'lines_id') === $line);
ok('13d old SIM is kept, retired, still linked for history', (int) fieldOf(Item_DeviceSimcard::class, $sim_old, 'lines_id') === $line && (int) fieldOf(Item_DeviceSimcard::class, $sim_old, 'states_id') === $retired);

// --- 14. Placeholder serials are not duplicates -------------------------------------
$na_one = newItem(Computer::class, ['name' => "T$stamp-NV1", 'entities_id' => 0, 'serial' => 'NOT-VISIBLE']);
$na_two = newItem(Computer::class, ['name' => "T$stamp-NV2", 'entities_id' => 0, 'serial' => 'NOT-VISIBLE']);
ok('14a two items with NOT-VISIBLE are both created', $na_one > 0 && $na_two > 0);

// --- 15. Company branding: name and logo, refused files, companies, logo URL ----------
/** Writes a solid square PNG. PHP has no System.Drawing, so the bytes are built here: zlib data, CRC-32 chunks. */
function writeTestPng(string $path, int $size): void
{
    $chunk = static fn(string $type, string $data): string
        => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    $row = "\x00" . str_repeat("\xc4\xa4\x77", $size); // filter byte, then gold #c4a477 pixels
    file_put_contents($path, "\x89PNG\r\n\x1a\n"
        . $chunk('IHDR', pack('NNCCCCC', $size, $size, 8, 2, 0, 0, 0))
        . $chunk('IDAT', gzcompress(str_repeat($row, $size)))
        . $chunk('IEND', ''));
}

$brand_png = sys_get_temp_dir() . "/sanam-brand-$stamp.png";
writeTestPng($brand_png, 64);
$brand_co = newItem(Entity::class, ['name' => "T$stamp-Brand", 'entities_id' => 0]);
Brand::save($brand_co, 'Brand Test Co', $brand_png, 'logo.png');
$saved = Brand::get($brand_co);
ok('15a display name and 64x64 PNG logo saved', $saved !== null && $saved['display_name'] === 'Brand Test Co' && $saved['logo_file'] === "$brand_co.png", json_encode($saved));
ok('15b logo stored in the var dir', is_file(Brand::storageDir() . "/$brand_co.png"), Brand::storageDir());

$fake_png = sys_get_temp_dir() . "/sanam-fake-$stamp.png";
file_put_contents($fake_png, "This is a text file, not an image.\n");
ok('15c text file renamed .png is refused', throwsWith(static fn() => Brand::save($brand_co, 'Should not save', $fake_png, 'fake.png'), 'PNG, JPEG or WebP'));
$after_refusal = Brand::get($brand_co);
ok('15d refused logo leaves the saved name and logo', $after_refusal !== null && $after_refusal['display_name'] === 'Brand Test Co' && $after_refusal['logo_file'] === "$brand_co.png");

$added = Brand::addCompany("T$stamp-Added", 0);
$added_row = firstRow('sanam_entities', ['id' => $added]);
ok('15e added company completename starts with SANAM >', str_starts_with((string) ($added_row['completename'] ?? ''), 'SANAM >'), (string) ($added_row['completename'] ?? ''));
ok('15f added company has its brand row', Brand::get($added) !== null);

global $CFG_SANAM;
$logo_expected = ($CFG_SANAM['root_doc'] ?? '') . "/plugins/sanamassets/front/logo.php?entity=$brand_co";
ok('15g logoUrl returns the logo page path', Brand::logoUrl($brand_co) === $logo_expected, Brand::logoUrl($brand_co));
ok('15h active entity is read from the core session key', Brand::activeEntity() === (int) $_SESSION['sanamactive_entity']);

@unlink($brand_png);
@unlink($fake_png);

echo "\n$pass passed, $fail failed\n";
exit($fail > 0 ? 1 : 0);
