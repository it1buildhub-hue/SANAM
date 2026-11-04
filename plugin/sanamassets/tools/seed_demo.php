<?php

/**
 * Fills a demo copy with made-up sample data: places, people, assets, custody, phone lines and one audit.
 *
 *   php seed_demo.php
 *
 * Runs only on the database named sanam_demo, so it can never add sample records to a real copy.
 * SANAM_CONFIG_DIR and SANAM_VAR_DIR select the instance; SANAM_ROOT selects the core.
 */

$sanam_root = getenv('SANAM_ROOT') ?: 'D:/Learn/sanam-core';
require_once $sanam_root . '/vendor/autoload.php';
global $kernel, $DB;
$kernel = new \Sanam\Kernel\Kernel();
$kernel->boot();

use SanamPlugin\Sanamassets\Campaign;
use SanamPlugin\Sanamassets\Custody;
use SanamPlugin\Sanamassets\Label;

function fail(string $message): never
{
    fwrite(STDERR, "seed_demo: $message\n");
    exit(1);
}

if ($DB->dbdefault !== 'sanam_demo') {
    fail("refusing to run on database '{$DB->dbdefault}'; only sanam_demo is allowed");
}

$_SESSION['sanamID'] = 2;
$_SESSION['sanamname'] = 'sanam.admin';
$_SESSION['sanamactiveentities'] = [0];
$_SESSION['sanamactive_entity'] = 0;
$_SESSION['sanamactiveprofile'] = ['id' => 4];
$_SESSION['sanamcronuserrunning'] = 'seed_demo';

function add(string $class, array $input): int
{
    $item = new $class();
    $id = $item->add($input);
    if (!$id) {
        fail("could not add $class " . ($input['name'] ?? ''));
    }
    return (int) $id;
}

function idByName(string $table, string $name): int
{
    global $DB;
    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => $table, 'WHERE' => ['name' => $name], 'LIMIT' => 1]) as $row) {
        return (int) $row['id'];
    }
    fail("'$name' is missing from $table");
}

function assetIdOf(string $class, int $id): string
{
    $item = new $class();
    $item->getFromDB($id);
    return (string) $item->fields['otherserial'];
}

// A real company server address is needed before labels can be made; this one is a reserved example name.
Config::setConfigurationValues('plugin:sanamassets', ['base_url' => 'https://assets.example.com']);

// --- Places --------------------------------------------------------------------------------
$location = static fn(string $name, int $parent = 0): int => add(Location::class, [
    'name' => $name, 'locations_id' => $parent, 'entities_id' => 0, 'is_recursive' => 1,
]);
$head   = $location('Head Office');
$it     = $location('IT Room', $head);
$finance = $location('Finance', $head);
$meeting = $location('Meeting Room', $head);
$store  = $location('Main Store');
$yard   = $location('Yard Office');

// --- People (all invented) -----------------------------------------------------------------
$company = [];
foreach (['Buildhub', 'BH Yards', 'Alarabia', 'FFO', 'Remas', 'Segma'] as $name) {
    $company[$name] = idByName('sanam_entities', $name);
}
$person = static fn(string $first, string $last, string $at): int => add(User::class, [
    'name' => strtolower("demo.$first"), 'firstname' => $first, 'realname' => $last,
    'entities_id' => $company[$at], 'is_active' => 1,
]);
$omar  = $person('Omar', 'Haddad', 'Buildhub');
$layla = $person('Layla', 'Nasser', 'Buildhub');
$yusuf = $person('Yusuf', 'Karim', 'BH Yards');
$sara  = $person('Sara', 'Mansour', 'Alarabia');
$ali   = $person('Ali', 'Rahman', 'FFO');
$mona  = $person('Mona', 'Saleh', 'Remas');

// --- Assets --------------------------------------------------------------------------------
$inUse = idByName('sanam_states', 'In use');
$stock = idByName('sanam_states', 'In stock');
$repair = idByName('sanam_states', 'In repair (off-site)');
$maker = [];
foreach (['Dell', 'HP', 'Lenovo', 'Apple', 'Samsung', 'Cisco', 'Canon'] as $name) {
    $maker[$name] = add(Manufacturer::class, ['name' => $name]);
}
$laptop = add(ComputerType::class, ['name' => 'Laptop']);
$desktop = add(ComputerType::class, ['name' => 'Desktop']);
$mobile = add(PhoneType::class, ['name' => 'Mobile']);
$modelOf = static function (string $class, string $name): int {
    return add($class, ['name' => $name]);
};

// class, name, company, place, state, maker, serial, extra fields
$assets = [
    'latitude' => [Computer::class, 'Latitude 5540', 'Buildhub', $finance, $inUse, 'Dell', 'DEMO-L5540-01', ['computertypes_id' => $laptop, 'computermodels_id' => $modelOf(ComputerModel::class, 'Latitude 5540')]],
    'elitebook' => [Computer::class, 'EliteBook 840', 'Buildhub', $it, $inUse, 'HP', 'DEMO-E840-02', ['computertypes_id' => $laptop, 'computermodels_id' => $modelOf(ComputerModel::class, 'EliteBook 840 G10')]],
    'thinkpad' => [Computer::class, 'ThinkPad T14', 'BH Yards', $yard, $inUse, 'Lenovo', 'DEMO-T14-03', ['computertypes_id' => $laptop, 'computermodels_id' => $modelOf(ComputerModel::class, 'ThinkPad T14')]],
    'macbook' => [Computer::class, 'MacBook Air', 'Alarabia', $meeting, $inUse, 'Apple', 'DEMO-MBA-04', ['computertypes_id' => $laptop, 'computermodels_id' => $modelOf(ComputerModel::class, 'MacBook Air 13')]],
    'optiplex' => [Computer::class, 'OptiPlex 7010', 'FFO', $finance, $inUse, 'Dell', 'DEMO-O7010-05', ['computertypes_id' => $desktop, 'computermodels_id' => $modelOf(ComputerModel::class, 'OptiPlex 7010')]],
    'prodesk' => [Computer::class, 'ProDesk 400', 'Remas', $store, $stock, 'HP', 'DEMO-P400-06', ['computertypes_id' => $desktop, 'computermodels_id' => $modelOf(ComputerModel::class, 'ProDesk 400 G9')]],
    'spare' => [Computer::class, 'Spare Latitude', 'Segma', $store, $repair, 'Dell', 'DEMO-L3540-07', ['computertypes_id' => $laptop]],
    'monitor1' => [Monitor::class, 'Dell 27 Monitor', 'Buildhub', $finance, $inUse, 'Dell', 'DEMO-P2723-08', []],
    'monitor2' => [Monitor::class, 'Samsung 24 Monitor', 'FFO', $finance, $inUse, 'Samsung', 'DEMO-S24-09', []],
    'iphone' => [Phone::class, 'iPhone 15', 'Buildhub', $head, $inUse, 'Apple', 'DEMO-356938035643801', ['phonetypes_id' => $mobile]],
    'galaxy' => [Phone::class, 'Galaxy S24', 'BH Yards', $yard, $inUse, 'Samsung', 'DEMO-356938035643802', ['phonetypes_id' => $mobile]],
    'printer' => [Printer::class, 'Canon Office Printer', 'Buildhub', $it, $inUse, 'Canon', 'DEMO-MF455-12', []],
    'switch' => [NetworkEquipment::class, 'Cisco Core Switch', 'Buildhub', $it, $inUse, 'Cisco', 'DEMO-C9200-13', []],
];
$made = [];
foreach ($assets as $key => [$class, $name, $at, $place, $state, $brand, $serial, $extra]) {
    $id = add($class, [
        'name' => $name, 'entities_id' => $company[$at], 'locations_id' => $place, 'states_id' => $state,
        'manufacturers_id' => $maker[$brand], 'serial' => $serial,
    ] + $extra);
    $made[$key] = [$class, $id, assetIdOf($class, $id)];
}

// --- Custody -------------------------------------------------------------------------------
$issue = static function (string $key, array $data) use ($made): int {
    [$class, $id] = $made[$key];
    return Custody::issue($class, $id, $data + ['condition' => 'Good', 'ack' => 'signed_paper']);
};
$issue('latitude', ['kind' => 'permanent', 'users_id' => $omar, 'accessories' => 'Charger, bag']);
$issue('monitor1', ['kind' => 'permanent', 'users_id' => $omar]);
$issue('iphone', ['kind' => 'permanent', 'users_id' => $layla, 'accessories' => 'Charger, case']);
$issue('thinkpad', ['kind' => 'permanent', 'users_id' => $yusuf, 'accessories' => 'Charger']);
$issue('galaxy', ['kind' => 'permanent', 'users_id' => $yusuf]);
$issue('optiplex', ['kind' => 'permanent', 'users_id' => $ali]);
$issue('macbook', ['kind' => 'temporary', 'users_id' => $sara, 'expected_return' => date('Y-m-d', strtotime('+14 days')), 'note' => 'Client visit']);
$late = $issue('elitebook', ['kind' => 'temporary', 'users_id' => $mona, 'expected_return' => date('Y-m-d', strtotime('+1 day')), 'note' => 'Conference loan']);
// A loan already past its date, so the dashboard's overdue list has something to show.
$DB->update('sanam_plugin_sanamassets_assignments', ['date_expected_return' => date('Y-m-d', strtotime('-5 days'))], ['id' => $late]);

// --- Phone lines ---------------------------------------------------------------------------
$operator = add(LineOperator::class, ['name' => 'Demo Telecom', 'mcc' => 999, 'mnc' => 1]);
foreach ([['CEO mobile', '+971500000101', 'Buildhub', $layla], ['Site manager', '+971500000102', 'BH Yards', $yusuf], ['Spare line', '+971500000103', 'Buildhub', 0], ['Office landline', '+97140000104', 'Alarabia', 0]] as [$name, $number, $at, $holder]) {
    add(Line::class, ['name' => $name, 'caller_num' => $number, 'lineoperators_id' => $operator, 'entities_id' => $company[$at], 'users_id' => $holder]);
}

// --- Labels --------------------------------------------------------------------------------
foreach (['latitude', 'elitebook', 'thinkpad', 'macbook', 'iphone', 'printer', 'switch'] as $key) {
    Label::record($made[$key][2], 'printed', 'Demo batch');
}
Label::record($made['latitude'][2], 'scanned', 'Demo');
Label::record($made['iphone'][2], 'scanned', 'Demo');

// --- One audit under way, with a location finding and rooms not yet covered ----------------
$campaign = Campaign::create('2026 Baseline Audit', 0, 'baseline');
$areaIt = Campaign::addArea($campaign, 'IT Room', $it);
$areaFinance = Campaign::addArea($campaign, 'Finance', $finance);
Campaign::addArea($campaign, 'Meeting Room', $meeting);
Campaign::addArea($campaign, 'Main Store', $store);
Campaign::start($campaign);
Campaign::verify($campaign, $made['switch'][2], $areaIt, 'qr', $it, 0, 'Good', '');
Campaign::verify($campaign, $made['printer'][2], $areaIt, 'qr', $it, 0, 'Good', '');
Campaign::setAreaStatus($campaign, $areaIt, 'done');
Campaign::verify($campaign, $made['latitude'][2], $areaFinance, 'qr', $finance, 0, 'Good', '');
Campaign::verify($campaign, $made['monitor2'][2], $areaFinance, 'manual', $meeting, 0, 'Good', 'Found in the meeting room');
Campaign::create('Q4 Spot Check', $company['BH Yards'], 'spot');

echo 'Demo data added: ' . count($made) . " assets, one audit under way.\n";
foreach ($made as $key => $row) {
    if ($key === 'latitude') {
        echo "Sample asset: {$row[2]}\nSample campaign: $campaign\n";
    }
}
