# SANAM Assets (SANAM 11 plugin)

Adds SANAM's asset rules to SANAM 11.0.x: one permanent asset ID per item, QR labels, a mobile scan page,
physical audit campaigns, custody and handover, and an actionable dashboard. Everything else (asset
types, locations, states, telecom lines, tickets) is native SANAM.

Plan of record: the approved plan in `C:\Users\buildhub\.claude\plans\also-right-now-there-floofy-owl.md`.

## Layout

| Path | Purpose |
|---|---|
| `setup.php`, `hook.php` | Plugin entry point, install (creates tables and rights), uninstall (keeps data) |
| `src/AssetId.php` | Sequential, never-reused asset IDs (`SNM-000001`), stored in `otherserial` |
| `src/AssetHooks.php` | Item hooks: assign ID, refuse duplicate serials, block ID changes, retire IDs on purge |
| `src/Campaign.php` | Audit campaigns: expected snapshot, one verification per item, areas, review, close, reopen |
| `src/Custody.php` | Issue, return, transfer, handover data, exit clearance |
| `src/Label.php` | QR URL (refuses localhost), QR SVG, label lifecycle |
| `src/Conflict.php`, `src/Discrepancy.php`, `src/Scan.php` | Identifier conflicts, audit findings, scan log and search |
| `src/Dashboard.php`, `src/Report.php`, `src/Menu.php` | Daily lists, CSV exports, menu entries |
| `front/*.php` | Pages: scan (with quick custody), register (auditor draft), campaigns, custody, telecom, employee, labels, label print, dashboard, reports, export, settings |
| `tools/seed_sanam.php` | Idempotent: lifecycle states, six custom asset types, two roles |
| `tools/seed_fields.php` | Idempotent: IMEI2, PoE, management IP through the Fields plugin |
| `tools/build_brand_locales.py` | SANAM display names from the official catalogues (no core edits) |
| `tools/backup_restore_test.ps1` | Dump, archive, restore rehearsal, table checksum comparison |
| `theme/sanam.scss` | SANAM palette (placeholder colours) |
| `tests/acceptance.php` | 61 service-level checks (plan section 15), run on the test instance only |
| `tests/seed_test_users.php`, `tests/http_roles.py` | Test accounts and HTTP access checks, test instance only |

## Environment

- Upstream release 11.0.12 (SHA-256 verified), PHP 8.3, MariaDB 11.8.
- Staging: `D:\Learn\sanam-core` on `http://127.0.0.1:8081`, database `sanam`.
- Test: same code, `SANAM_CONFIG_DIR=D:\Learn\sanam-test\config`, `SANAM_VAR_DIR=D:\Learn\sanam-test\files`,
  database `sanam_test`, server on `http://127.0.0.1:8082`.
- Plugins on staging: `sanamassets` 1.0.0, `fields` 1.24.7, `datainjection` 2.15.12 (all GPL).

## Install on a SANAM 11.0.x instance

```bash
# copy this folder to <sanam>/plugins/sanamassets (without .git, tests/, tools/, theme/)
php bin/console plugin:install sanamassets -n
php bin/console plugin:activate sanamassets -n
php tools/seed_sanam.php          # states, custom asset types, roles
php tools/seed_fields.php         # needs the fields plugin active
cp theme/sanam.scss <SANAM_VAR_DIR>/_themes/sanam.scss
php bin/console config:set palette sanam
python -I tools/build_brand_locales.py <sanam_root> <SANAM_VAR_DIR>
php bin/console cache:clear
```

Then set the server address in **SANAM settings** (`/plugins/sanamassets/front/config.php`). It must not be
`localhost` or `127.0.0.1`; labels refuse those addresses.

## Tests

```bash
# acceptance (service level, 61 checks)
set SANAM_CONFIG_DIR=D:\Learn\sanam-test\config
set SANAM_VAR_DIR=D:\Learn\sanam-test\files
php tests/acceptance.php

# HTTP access rules (needs test accounts and the test server on 8082)
php tests/seed_test_users.php
python -I tests/http_roles.py     # 31 checks
```

The acceptance script refuses to run unless `SANAM_CONFIG_DIR` points at the test instance.

## Operational rules to keep

- **Uninstall revokes rights.** After `plugin:uninstall` and `plugin:install`, run `tools/seed_sanam.php` again,
  otherwise the custom roles lose their plugin rights.
- **Data is never dropped by uninstall.** Tables are removed only by a backed-up, manual step.
- **Translations are cached.** Run `cache:clear` after changing `files/_locales` or the theme.
- **SANAM 11 only loads custom themes as SCSS** (`src/Sanam/UI/Theme.php`). A `.css` theme breaks the error page.
- **Do not call `Session::checkCSRF($_POST)` in plugin pages.** The kernel already checks CSRF on every POST
  (`CheckCsrfListener`) and consumes the token. A second check fails with 403. This bug blocked every form until fixed.
- **Use `Ui::self()`, not `$_SERVER['PHP_SELF']`.** The PHP built-in server behind a router reports `\index.php`.
- **Item hooks are per class.** `AssetHooks::managedClasses()` lists every class that gets an ID, including
  each custom asset type. Add a new custom type and the plugin picks it up on the next request.

## Known gaps (not done here)

| Item | Why | Needed to finish |
|---|---|---|
| Stable `/a/{id}` link | Current label URL is `front/scan.php?asset=...`. Apache rewrite is part of the server setup | Production server |
| Camera scanning on phones | Browsers only open the camera on HTTPS | HTTPS certificate for the company name |
| Phone test over company Wi-Fi | No company network or phones available here | Pilot on real devices |
| "Condition" as a controlled list | Free text for now. A custom dropdown class is needed | Owner's list of values |
| Page title, footer and logo still say SANAM | Hard-coded in core PHP and templates; editing core is ruled out | SANAM logo file and a decision on a JS/header hook |
| SANAM brand colours | Palette values are placeholders | Confirmed colours |
| Locations, departments, company list | No real data available | Site and department lists from the owner |
| Historical 2025 files | Not on this PC; only `Buildhub_2026_Asset_Control.xlsx` found | Original files, reference only |
| Agent-based inventory and locks | No inventory agent deployed | Phase 4 decision |
| SIM replacement keeps line history | Core behaviour, not tested here | Test with real SIMs |
| Maintenance tickets with cost and warranty fields | Native tickets only | Decision on fields |
| Backup on a Windows server | Rehearsed on this PC only | Production server and schedule |
| Timezone tables | The zoneinfo files available here do not load into MariaDB on Windows, so `database:enable_timezones` still refuses | On the Linux server: `mysql_tzinfo_to_sql /usr/share/zoneinfo \| mysql -u root mysql`, then `php bin/console database:enable_timezones` |
