<?php

/**
 * TEST INSTANCE ONLY. Creates one user per role so HTTP access rules can be checked.
 * Refuses to run unless SANAM_CONFIG_DIR contains sanam-test.
 *
 *   SANAM_CONFIG_DIR=D:\Learn\sanam-test\config SANAM_VAR_DIR=D:\Learn\sanam-test\files php seed_test_users.php
 */

if (!str_contains((string) getenv('SANAM_CONFIG_DIR'), 'sanam-test')) {
    fwrite(STDERR, "Refusing: this seeds test accounts and must only run on the test instance.\n");
    exit(2);
}

$sanam_root = getenv('SANAM_ROOT') ?: 'D:/Learn/sanam-core';
require_once $sanam_root . '/vendor/autoload.php';
global $kernel, $DB;
$kernel = new \Sanam\Kernel\Kernel();
$kernel->boot();

const TEST_PASSWORD = 'Sanam-Test-Pass-2026';

function profileId(string $name): int
{
    global $DB;
    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_profiles', 'WHERE' => ['name' => $name], 'LIMIT' => 1]) as $r) {
        return (int) $r['id'];
    }
    return 0;
}

$roles = [
    'test.observer' => 'Observer',
    'test.auditor'  => 'SANAM Auditor',
    'test.manager'  => 'SANAM Asset Manager',
];

foreach ($roles as $login => $profile) {
    $user = new User();
    $existing = $user->getFromDBbyName($login);
    if (!$existing) {
        $user_id = (int) $user->add([
            'name'      => $login,
            'password'  => TEST_PASSWORD,
            'password2' => TEST_PASSWORD,
            'is_active' => 1,
            'entities_id' => 0,
        ]);
    } else {
        $user_id = (int) $user->getID();
    }

    $profile_id = profileId($profile);
    $link = new Profile_User();
    $has = false;
    foreach ($DB->request(['SELECT' => ['id'], 'FROM' => 'sanam_profiles_users', 'WHERE' => ['users_id' => $user_id, 'profiles_id' => $profile_id]]) as $r) {
        $has = true;
    }
    if (!$has && $profile_id > 0) {
        $link->add(['users_id' => $user_id, 'profiles_id' => $profile_id, 'entities_id' => 0, 'is_recursive' => 1]);
    }
    echo "$login ($profile): user=$user_id profile=$profile_id\n";
}
