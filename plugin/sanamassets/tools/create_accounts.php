<?php

/**
 * Creates or resets the SANAM operator accounts and disables the install's default test accounts.
 *
 *   php create_accounts.php
 *
 * SANAM_CONFIG_DIR and SANAM_VAR_DIR select the instance, as in instance_setup.ps1.
 * SANAM_ROOT selects the core (default D:/Learn/sanam-core).
 *
 * Accounts, for the companies that are direct children of the SANAM root entity:
 *   sanam.admin               Super-Admin on the root entity, recursive (the install's user id 2, renamed)
 *   sanam.manager             SANAM Asset Manager on the root entity, recursive
 *   it.<slug>, audit.<slug>   SANAM Asset Manager and SANAM Auditor on the company entity, not recursive
 *   tech, normal, post-only   disabled, if they exist
 *
 * The database must be 'sanam', 'sanam_test' or 'sanam_demo'. Each account gets a new random 16-character password,
 * set through the user update. Every write is read back and checked, so a refused update stops the run.
 * Existing accounts get their fields reconciled and a new password. The table of login, password, role
 * and company is printed to stdout at the end. Nothing is written to a file.
 *
 * Writes run under the maintenance session key $_SESSION['sanamcronuserrunning'], which Session::isCron()
 * reads to lift the rights checks. The run is not transactional. If it fails, fix the reported problem
 * and run it again.
 */

// Bootstrap, the same as seed_sanam.php, against the SANAM core.
$sanam_root = getenv('SANAM_ROOT') ?: 'D:/Learn/sanam-core';
require_once $sanam_root . '/vendor/autoload.php';
global $kernel;
$kernel = new \Sanam\Kernel\Kernel();
$kernel->boot();
global $DB;

final class AccountSetup
{
    private const ROOT_ENTITY = 'SANAM';
    private const ADMIN_USER_ID = 2; // USER_SANAM in install/empty_data.php
    private const ALLOWED_DATABASES = ['sanam', 'sanam_test', 'sanam_demo'];
    private const DEFAULT_ACCOUNTS = ['tech', 'normal', 'post-only'];
    private const COMPANIES = [
        'Buildhub', 'BH Yards', 'Alarabia', 'FFO', 'Remas',
        'Segma', 'Tadeem', 'MEP', 'TASH TASH', 'NHR',
    ];
    private const ALL_COMPANIES = 'SANAM (all companies)';
    private const PASSWORD_LENGTH = 16;
    private const PW_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const PW_LOWER = 'abcdefghijklmnopqrstuvwxyz';
    private const PW_DIGIT = '0123456789';
    private const PW_SYMBOL = '!#%+-=?@';

    public static function run(): void
    {
        try {
            self::execute();
        } catch (Throwable $e) {
            self::fail($e::class . ': ' . $e->getMessage());
        }
    }

    private static function execute(): void
    {
        global $DB;

        if (PHP_SAPI !== 'cli') {
            self::fail('run this script from the command line');
        }
        if (!$DB->connected) {
            self::fail('no database connection');
        }
        if (!in_array($DB->dbdefault, self::ALLOWED_DATABASES, true)) {
            self::fail("refusing to run on database '{$DB->dbdefault}'; only 'sanam', 'sanam_test' and 'sanam_demo' are allowed");
        }
        if ($DB->isSlave()) {
            self::fail('the database connection is read-only');
        }

        // Maintenance session, set before the first update. Session::isCron() is true from here on,
        // so the core's rights checks do not silently drop the field and password updates.
        $_SESSION['sanamcronuserrunning'] = 'create_accounts';
        $_SESSION['sanam_currenttime'] = date('Y-m-d H:i:s');
        if (!Session::isCron()) {
            self::fail('the maintenance session is not active');
        }

        // Everything is resolved and checked before the first write.
        [$accounts, $disable] = self::plan();

        $result = [];
        foreach ($accounts as $item) {
            $result[] = self::provision($item['spec'], $item['existing']);
        }
        foreach ($disable as $user_id) {
            self::updateUser($user_id, ['is_active' => 0]);
            self::verifyUser($user_id, ['is_active' => 0], '');
        }

        self::printTable($result);
    }

    /**
     * @return array{0: list<array{spec: array<string, mixed>, existing: ?array<string, mixed>}>, 1: list<int>}
     */
    private static function plan(): array
    {
        $root = self::rows('sanam_entities', ['id' => 0]);
        if (count($root) !== 1 || $root[0]['name'] !== self::ROOT_ENTITY) {
            self::fail("entity id 0 must exist and be named '" . self::ROOT_ENTITY . "'");
        }
        $super_admin   = self::profileId('Super-Admin');
        $asset_manager = self::profileId('SANAM Asset Manager');
        $auditor       = self::profileId('SANAM Auditor');

        // The install's default administrator is user id 2 (login 'sanam'). It becomes 'sanam.admin'.
        $admin_rows = self::rows('sanam_users', ['id' => self::ADMIN_USER_ID]);
        if (count($admin_rows) !== 1) {
            self::fail('user id 2, the default administrator, does not exist');
        }
        $admin = $admin_rows[0];
        if (!in_array($admin['name'], ['sanam', 'sanam.admin'], true)) {
            self::fail("user id 2 is '{$admin['name']}', expected the default administrator 'sanam'");
        }
        self::checkLocal($admin);
        $clash = self::localUser('sanam.admin');
        if ($clash !== null && (int) $clash['id'] !== self::ADMIN_USER_ID) {
            self::fail("another account is already named 'sanam.admin'");
        }

        $accounts = [
            [
                'spec' => [
                    'login'     => 'sanam.admin',
                    'firstname' => 'SANAM Group',
                    'realname'  => 'Administrator',
                    'comment'   => 'Super administrator, SANAM Group',
                    'entity'    => 0,
                    'profile'   => $super_admin,
                    'recursive' => 1,
                    'role'      => 'Super-Admin',
                    'company'   => self::ALL_COMPANIES,
                ],
                'existing' => $admin,
            ],
            [
                'spec' => [
                    'login'     => 'sanam.manager',
                    'firstname' => 'IT',
                    'realname'  => 'SANAM Group',
                    'comment'   => 'IT asset manager, all companies',
                    'entity'    => 0,
                    'profile'   => $asset_manager,
                    'recursive' => 1,
                    'role'      => 'SANAM Asset Manager',
                    'company'   => self::ALL_COMPANIES,
                ],
                'existing' => self::localUser('sanam.manager'),
            ],
        ];

        foreach (self::COMPANIES as $company) {
            $entity = self::companyEntityId($company);
            $slug   = self::slugFor($company);
            $accounts[] = [
                'spec' => [
                    'login'     => "it.$slug",
                    'firstname' => 'IT',
                    'realname'  => $company,
                    'comment'   => "IT asset manager - $company",
                    'entity'    => $entity,
                    'profile'   => $asset_manager,
                    'recursive' => 0,
                    'role'      => 'SANAM Asset Manager',
                    'company'   => $company,
                ],
                'existing' => self::localUser("it.$slug"),
            ];
            $accounts[] = [
                'spec' => [
                    'login'     => "audit.$slug",
                    'firstname' => 'Auditor',
                    'realname'  => $company,
                    'comment'   => "Auditor - $company",
                    'entity'    => $entity,
                    'profile'   => $auditor,
                    'recursive' => 0,
                    'role'      => 'SANAM Auditor',
                    'company'   => $company,
                ],
                'existing' => self::localUser("audit.$slug"),
            ];
        }

        $logins = array_map(static fn(array $item): string => $item['spec']['login'], $accounts);
        if (count(array_unique($logins)) !== count($logins)) {
            self::fail('two accounts would get the same login');
        }

        $disable = [];
        foreach (self::DEFAULT_ACCOUNTS as $login) {
            foreach (self::exact(self::rows('sanam_users', ['name' => $login]), 'name', $login) as $row) {
                $disable[] = (int) $row['id'];
            }
        }

        return [$accounts, $disable];
    }

    /**
     * Creates or updates one account, links it to its profile and sets a new password.
     *
     * @param array<string, mixed>  $spec
     * @param ?array<string, mixed> $existing
     * @return array{login: string, password: string, role: string, company: string}
     */
    private static function provision(array $spec, ?array $existing): array
    {
        $user_id = $existing === null ? self::createUser($spec) : (int) $existing['id'];
        self::ensureLink($user_id, (int) $spec['profile'], (int) $spec['entity'], (int) $spec['recursive']);

        $fields = [
            'name'        => $spec['login'],
            'realname'    => $spec['realname'],
            'firstname'   => $spec['firstname'],
            'comment'     => $spec['comment'],
            'entities_id' => $spec['entity'],
            'profiles_id' => $spec['profile'],
            'is_active'   => 1,
        ];
        $password = self::generatePassword();
        self::updateUser($user_id, $fields + ['password' => $password, 'password2' => $password]);
        self::verifyUser($user_id, $fields, $password);

        return [
            'login'    => $spec['login'],
            'password' => $password,
            'role'     => $spec['role'],
            'company'  => $spec['company'],
        ];
    }

    /** @param array<string, mixed> $spec */
    private static function createUser(array $spec): int
    {
        $user = new User();
        $id = $user->add([
            'name'        => $spec['login'],
            'authtype'    => Auth::DB_SANAM,
            'auths_id'    => 0,
            'entities_id' => $spec['entity'],
            'is_active'   => 1,
            // The initial assignment. User::post_addItem uses these keys, so the default profile is not attached.
            '_profiles_id'  => $spec['profile'],
            '_entities_id'  => $spec['entity'],
            '_is_recursive' => $spec['recursive'],
        ]);
        if (!$id) {
            self::fail("SANAM refused to create '{$spec['login']}'");
        }
        return (int) $id;
    }

    private static function ensureLink(int $user_id, int $profile_id, int $entity_id, int $recursive): void
    {
        $criteria = [
            'users_id'     => $user_id,
            'profiles_id'  => $profile_id,
            'entities_id'  => $entity_id,
            'is_recursive' => $recursive,
        ];
        if (self::rows('sanam_profiles_users', $criteria) !== []) {
            return;
        }
        $link = new Profile_User();
        if (!$link->add($criteria + ['is_dynamic' => 0]) || self::rows('sanam_profiles_users', $criteria) === []) {
            self::fail("could not give user id $user_id profile id $profile_id on entity $entity_id");
        }
    }

    /** @param array<string, mixed> $fields */
    private static function updateUser(int $user_id, array $fields): void
    {
        $user = new User();
        if (!$user->update(['id' => $user_id] + $fields)) {
            self::fail("SANAM refused to update user id $user_id");
        }
    }

    /**
     * Reads the row back and checks that every field and the password were stored.
     *
     * @param array<string, mixed> $fields
     */
    private static function verifyUser(int $user_id, array $fields, string $password): void
    {
        $rows = self::rows('sanam_users', ['id' => $user_id]);
        if (count($rows) !== 1) {
            self::fail("user id $user_id is missing after the update");
        }
        $row = $rows[0];
        foreach ($fields as $field => $value) {
            if ((string) $row[$field] !== (string) $value) {
                self::fail("user id $user_id: '$field' is '{$row[$field]}', expected '$value'; the update did not stick");
            }
        }
        if ($password !== '' && !Auth::checkPassword($password, (string) $row['password'])) {
            self::fail("user id $user_id: the new password did not take effect");
        }
    }

    private static function generatePassword(): string
    {
        $all = self::PW_UPPER . self::PW_LOWER . self::PW_DIGIT . self::PW_SYMBOL;
        // One character from each class, so every class is present; the rest come from the full set.
        $chars = [];
        foreach ([self::PW_UPPER, self::PW_LOWER, self::PW_DIGIT, self::PW_SYMBOL] as $class) {
            $chars[] = $class[self::randomIndex(strlen($class))];
        }
        while (count($chars) < self::PASSWORD_LENGTH) {
            $chars[] = $all[self::randomIndex(strlen($all))];
        }
        // Fisher-Yates shuffle, so the class characters do not always come first.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = self::randomIndex($i + 1);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        return implode('', $chars);
    }

    /** Uniform integer in [0, $n) taken from random_bytes(), rejecting values that would bias the result. */
    private static function randomIndex(int $n): int
    {
        $limit = 256 - (256 % $n);
        do {
            $byte = ord(random_bytes(1));
        } while ($byte >= $limit);
        return $byte % $n;
    }

    private static function slugFor(string $company): string
    {
        $slug = (string) preg_replace('/[^a-z0-9]/', '', strtolower($company));
        if ($slug === '') {
            self::fail("no login slug can be made from company '$company'");
        }
        return $slug;
    }

    /** @return list<array<string, mixed>> */
    private static function rows(string $table, array $criteria): array
    {
        global $DB;

        $found = [];
        foreach ($DB->request(['FROM' => $table, 'WHERE' => $criteria]) as $row) {
            $found[] = $row;
        }
        return $found;
    }

    /**
     * Keeps the rows whose $field is exactly $value. MySQL comparisons ignore case and trailing
     * spaces, so the match is checked again in PHP.
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private static function exact(array $rows, string $field, string $value): array
    {
        return array_values(array_filter($rows, static fn(array $row): bool => $row[$field] === $value));
    }

    private static function profileId(string $name): int
    {
        $hits = self::exact(self::rows('sanam_profiles', ['name' => $name]), 'name', $name);
        if (count($hits) !== 1) {
            self::fail("expected exactly one profile named '$name', found " . count($hits));
        }
        return (int) $hits[0]['id'];
    }

    private static function companyEntityId(string $company): int
    {
        $completename = self::ROOT_ENTITY . ' > ' . $company;
        $hits = self::exact(
            self::rows('sanam_entities', ['completename' => $completename, 'entities_id' => 0]),
            'completename',
            $completename
        );
        if (count($hits) !== 1) {
            self::fail("expected exactly one entity '$completename', found " . count($hits));
        }
        return (int) $hits[0]['id'];
    }

    /** @return ?array<string, mixed> The local account with this exact login, or null. */
    private static function localUser(string $login): ?array
    {
        $hits = self::exact(self::rows('sanam_users', ['name' => $login]), 'name', $login);
        if (count($hits) > 1) {
            self::fail("more than one account is named '$login'");
        }
        if ($hits === []) {
            return null;
        }
        self::checkLocal($hits[0]);
        return $hits[0];
    }

    /** @param array<string, mixed> $user */
    private static function checkLocal(array $user): void
    {
        if ((int) $user['authtype'] !== Auth::DB_SANAM || (int) $user['auths_id'] !== 0) {
            self::fail("'{$user['name']}' exists but is not a local database account");
        }
        if ((int) $user['is_deleted'] === 1) {
            self::fail("'{$user['name']}' is in the trash; restore or purge it, then run again");
        }
    }

    /** @param list<array{login: string, password: string, role: string, company: string}> $accounts */
    private static function printTable(array $accounts): void
    {
        $table = [['login', 'password', 'role', 'company']];
        foreach ($accounts as $account) {
            $table[] = [$account['login'], $account['password'], $account['role'], $account['company']];
        }

        $widths = array_fill(0, 4, 0);
        foreach ($table as $line) {
            foreach ($line as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        foreach ($table as $n => $line) {
            $cells = [];
            foreach ($line as $i => $cell) {
                $cells[] = str_pad($cell, $widths[$i]);
            }
            echo rtrim(implode('  ', $cells)), PHP_EOL;
            if ($n === 0) {
                echo implode('  ', array_map(static fn(int $w): string => str_repeat('-', $w), $widths)), PHP_EOL;
            }
        }
    }

    private static function fail(string $message): never
    {
        fwrite(STDERR, 'create_accounts: ' . $message . PHP_EOL);
        exit(1);
    }
}

AccountSetup::run();
