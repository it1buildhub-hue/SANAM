<?php

/**
 * Seeds SANAM reference data into a SANAM instance. Safe to run more than once.
 *
 *   php seed_sanam.php
 *
 * Uses the instance selected by SANAM_CONFIG_DIR / SANAM_VAR_DIR (test or staging).
 * Adds: lifecycle states, custom asset types with their fields, and two roles
 * (SANAM Auditor, SANAM Asset Manager). Nothing here touches asset records.
 */

$sanam_root = getenv('SANAM_ROOT') ?: 'D:/Learn/sanam-core';
require_once $sanam_root . '/vendor/autoload.php';
global $kernel;
$kernel = new \Sanam\Kernel\Kernel();
$kernel->boot();
global $DB;

use Sanam\Asset\AssetDefinition;
use Sanam\Asset\CustomFieldDefinition;
use Sanam\Asset\CustomFieldType\NumberType;
use Sanam\Asset\CustomFieldType\StringType;
use Sanam\Asset\CustomFieldType\TextType;

function existsByName(string $table, string $name): int
{
    global $DB;
    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => $table, 'WHERE' => ['name' => $name], 'LIMIT' => 1]) as $row) {
        return (int) $row['id'];
    }
    return 0;
}

// --- 1. Lifecycle states (status is separate from condition and audit result) -------------
$states = [
    'Draft (to enrich)', 'In stock', 'In use', 'On loan (temporary)', 'In repair (on-site)',
    'In repair (off-site)', 'Reserved', 'Pending retirement', 'Retired', 'Disposed',
    'Lost (confirmed)', 'Under investigation',
];
$added = 0;
foreach ($states as $name) {
    if (existsByName('sanam_states', $name) === 0) {
        (new State())->add(['name' => $name, 'entities_id' => 0, 'is_recursive' => 1]);
        $added++;
    }
}
echo "states added: $added\n";

// --- 2. Custom asset types for categories the native types do not cover --------------------
$infocom = 'Sanam\\Asset\\Capacity\\HasInfocomCapacity';
$documents = 'Sanam\\Asset\\Capacity\\HasDocumentsCapacity';
$history = 'Sanam\\Asset\\Capacity\\HasHistoryCapacity';
$netport = 'Sanam\\Asset\\Capacity\\HasNetworkPortCapacity';
$reservable = 'Sanam\\Asset\\Capacity\\IsReservableCapacity';

$definitions = [
    'CCTVCamera' => ['label' => 'CCTV camera', 'capacities' => [$infocom, $documents, $history, $netport], 'fields' => [
        ['nvr_name', 'NVR', StringType::class], ['nvr_channel', 'NVR channel', NumberType::class],
        ['switch_port', 'Switch and port', StringType::class], ['ip_address', 'IP address', StringType::class],
        ['placement', 'Placement and access', TextType::class],
    ]],
    'DisplayDevice' => ['label' => 'Display (TV, projector)', 'capacities' => [$infocom, $documents, $history], 'fields' => [
        ['resolution', 'Resolution', StringType::class], ['screen_size', 'Screen size (inch)', NumberType::class],
    ]],
    'MediaEquipment' => ['label' => 'Photography and media', 'capacities' => [$infocom, $documents, $history, $reservable], 'fields' => [
        ['mount_type', 'Mount or lens type', StringType::class], ['battery_count', 'Battery count', NumberType::class],
    ]],
    'AudioEquipment' => ['label' => 'Sound system', 'capacities' => [$infocom, $documents, $history], 'fields' => [
        ['power_watts', 'Power (W)', NumberType::class],
    ]],
    'FacilityDevice' => ['label' => 'Facility device (attendance, UPS, other)', 'capacities' => [$infocom, $documents, $history, $netport], 'fields' => [
        ['capacity_va', 'Capacity (VA)', NumberType::class],
    ]],
    'EquipmentKit' => ['label' => 'Equipment kit', 'capacities' => [$infocom, $documents, $history, $reservable], 'fields' => [
        ['contents', 'Kit contents', TextType::class],
    ]],
];

foreach ($definitions as $system_name => $def) {
    $existing = $DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_assets_assetdefinitions', 'WHERE' => ['system_name' => $system_name], 'LIMIT' => 1]);
    $definition_id = 0;
    foreach ($existing as $row) {
        $definition_id = (int) $row['id'];
    }
    if ($definition_id === 0) {
        $definition = new AssetDefinition();
        $definition_id = (int) $definition->add([
            'system_name' => $system_name,
            'label'       => $def['label'],
            'is_active'   => 1,
            'capacities'  => array_map(static fn(string $class) => ['name' => $class], $def['capacities']),
        ]);
        echo "definition $system_name: " . ($definition_id ? 'created' : 'FAILED') . "\n";
        if ($definition_id === 0) {
            echo '  reason: ' . strip_tags(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? $_SESSION['messages'] ?? [])) . "\n";
        }
    } else {
        echo "definition $system_name: exists\n";
    }
    if ($definition_id === 0) {
        continue;
    }

    foreach ($def['fields'] as [$field, $label, $type]) {
        $has = $DB->request(['COUNT' => 'c', 'FROM' => 'sanam_assets_customfielddefinitions', 'WHERE' => ['assets_assetdefinitions_id' => $definition_id, 'system_name' => $field]]);
        $count = 0;
        foreach ($has as $r) {
            $count = (int) $r['c'];
        }
        if ($count === 0) {
            (new CustomFieldDefinition())->add([
                'assets_assetdefinitions_id' => $definition_id,
                'system_name'                => $field,
                'label'                      => $label,
                'type'                       => $type,
            ]);
        }
    }
}

// --- 3. Roles ---------------------------------------------------------------------------------
function ensureProfile(string $name): int
{
    global $DB;
    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_profiles', 'WHERE' => ['name' => $name], 'LIMIT' => 1]) as $row) {
        return (int) $row['id'];
    }
    $profile = new Profile();
    return (int) $profile->add(['name' => $name, 'interface' => 'central']);
}

$core = [
    Computer::class, Monitor::class, Peripheral::class, Phone::class,
    Printer::class, NetworkEquipment::class, Location::class, State::class, Line::class, Infocom::class,
];
$custom_rights = [];
foreach (array_keys($definitions) as $system_name) {
    $custom_rights['asset_' . strtolower($system_name)] = true;
}

$auditor = ensureProfile('SANAM Auditor');
$rights = ['plugin_sanamassets' => READ, 'plugin_sanamassets_audit' => READ | UPDATE];
foreach ($core as $class) {
    $rights[$class::$rightname] = READ;
}
// Auditors may create draft items of these types (plan: registration as draft during an audit).
$draft_types = [Computer::class, Monitor::class, Peripheral::class, Phone::class, Printer::class, NetworkEquipment::class];
foreach ($draft_types as $class) {
    $rights[$class::$rightname] = READ | CREATE;
}
foreach (array_keys($custom_rights) as $name) {
    $rights[$name] = READ;
}
ProfileRight::updateProfileRights($auditor, $rights);

$manager = ensureProfile('SANAM Asset Manager');
$rights = ['plugin_sanamassets' => READ | UPDATE | CREATE | DELETE, 'plugin_sanamassets_audit' => READ | UPDATE | CREATE | DELETE];
foreach ($core as $class) {
    $rights[$class::$rightname] = READ | UPDATE | CREATE;
}
foreach (array_keys($custom_rights) as $name) {
    $rights[$name] = READ | UPDATE | CREATE;
}
ProfileRight::updateProfileRights($manager, $rights);

echo "roles ready: SANAM Auditor=$auditor, SANAM Asset Manager=$manager\n";
