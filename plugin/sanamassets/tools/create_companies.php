<?php

/**
 * Creates the SANAM company tree: the root entity is named SANAM, and each company in the list is a
 * direct child of the root. Safe to run again: existing names are kept, missing ones are created.
 *
 *   php create_companies.php
 *
 * SANAM_CONFIG_DIR and SANAM_VAR_DIR select the instance; SANAM_ROOT selects the core
 * (default D:/Learn/sanam-core). The database must be 'sanam', 'sanam_test' or 'sanam_demo'.
 */

$sanam_root = getenv('SANAM_ROOT') ?: 'D:/Learn/sanam-core';
require_once $sanam_root . '/vendor/autoload.php';
global $kernel, $DB;
$kernel = new \Sanam\Kernel\Kernel();
$kernel->boot();

const ROOT_NAME = 'SANAM';
const COMPANIES = [
    'Buildhub', 'BH Yards', 'Alarabia', 'FFO', 'Remas',
    'Segma', 'Tadeem', 'MEP', 'TASH TASH', 'NHR',
];

function fail(string $message): void
{
    fwrite(STDERR, "create_companies: $message\n");
    exit(1);
}

if (PHP_SAPI !== 'cli') {
    fail('run from the command line');
}
if (!in_array($DB->dbdefault, ['sanam', 'sanam_test', 'sanam_demo'], true)) {
    fail("refusing to run on database '{$DB->dbdefault}'");
}

// Maintenance mode, as in create_accounts.php: without it the rights filter drops the name change.
$_SESSION['sanamcronuserrunning'] = 'create_companies';

$root = new Entity();
if (!$root->getFromDB(0)) {
    fail('root entity 0 is missing');
}
if ($root->fields['name'] !== ROOT_NAME) {
    $root->update(['id' => 0, 'name' => ROOT_NAME]);
    $root->getFromDB(0);
    if ($root->fields['name'] !== ROOT_NAME) {
        fail('the root entity was not renamed');
    }
    echo "root entity renamed to " . ROOT_NAME . "\n";
}

foreach (COMPANIES as $name) {
    $found = false;
    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_entities', 'WHERE' => ['name' => $name, 'entities_id' => 0], 'LIMIT' => 1]) as $row) {
        $found = true;
    }
    if ($found) {
        echo "exists: $name\n";
        continue;
    }
    $entity = new Entity();
    $id = $entity->add(['name' => $name, 'entities_id' => 0]);
    if ($id === false) {
        fail("could not create $name");
    }
    echo "created: $name (entity $id)\n";
}

echo "companies ready under " . ROOT_NAME . "\n";
