<?php

/**
 * Adds the native-type fields that SANAM does not provide (IMEI2, PoE, management IP)
 * through the Fields plugin. Idempotent. Requires the "fields" plugin to be active.
 *
 *   php seed_fields.php
 */

$sanam_root = getenv('SANAM_ROOT') ?: 'D:/Learn/sanam-core';
require_once $sanam_root . '/vendor/autoload.php';
global $kernel, $DB;
$kernel = new \Sanam\Kernel\Kernel();
$kernel->boot();

if (!Plugin::isPluginActive('fields')) {
    fwrite(STDERR, "The fields plugin is not active.\n");
    exit(2);
}

$containers = [
    'sanam_phone' => [
        'label'     => 'SANAM phone',
        'itemtypes' => ['Phone'],
        'fields'    => [
            ['imei2', 'IMEI 2', 'text'],
        ],
    ],
    'sanam_network' => [
        'label'     => 'SANAM network',
        'itemtypes' => ['NetworkEquipment'],
        'fields'    => [
            ['poe_supported', 'PoE supported', 'yesno'],
            ['poe_budget_w', 'PoE budget (W)', 'number'],
            ['management_ip', 'Management IP', 'text'],
        ],
    ],
];

foreach ($containers as $name => $spec) {
    $container = new PluginFieldsContainer();
    $container_id = 0;
    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_plugin_fields_containers', 'WHERE' => ['name' => $name], 'LIMIT' => 1]) as $row) {
        $container_id = (int) $row['id'];
    }
    if ($container_id === 0) {
        $container_id = (int) $container->add([
            'name'      => $name,
            'label'     => $spec['label'],
            'itemtypes' => $spec['itemtypes'],
            'type'      => 'dom',
            'is_active' => 1,
        ]);
        echo "container $name: " . ($container_id ? 'created' : 'FAILED') . "\n";
        if ($container_id === 0) {
            echo '  reason: ' . strip_tags(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'] ?? $_SESSION['messages'] ?? [])) . "\n";
        }
    } else {
        echo "container $name: exists\n";
    }
    if ($container_id === 0) {
        continue;
    }

    foreach ($spec['fields'] as [$field_name, $label, $type]) {
        $count = 0;
        foreach ($DB->request(['COUNT' => 'c', 'FROM' => 'sanam_plugin_fields_fields', 'WHERE' => ['plugin_fields_containers_id' => $container_id, 'name' => $field_name]]) as $row) {
            $count = (int) $row['c'];
        }
        if ($count === 0) {
            $field = new PluginFieldsField();
            $id = (int) $field->add([
                'plugin_fields_containers_id' => $container_id,
                'name'      => $field_name,
                'label'     => $label,
                'type'      => $type,
                'is_active' => 1,
            ]);
            echo "  field $field_name: " . ($id ? 'created' : 'FAILED') . "\n";
        }
    }
}
echo "fields seed done\n";
