<?php

/**
 * Install / uninstall for the SANAM Assets plugin.
 *
 * Uninstall deliberately keeps all data tables: asset identities and audit
 * history must never be destroyed by a plugin toggle. Tables are removed only
 * by an explicit, backed-up manual procedure.
 *
 * @license GPLv3+
 */

use Sanam\Plugin\Hooks;
use SanamPlugin\Sanamassets\Schema;

function plugin_sanamassets_install()
{
    global $DB;

    foreach (Schema::tables() as $name => $sql) {
        if (!$DB->tableExists($name)) {
            $DB->doQuery($sql);
        }
    }

    $DB->doQuery(
        'INSERT IGNORE INTO sanam_plugin_sanamassets_sequences (id, next_value) VALUES (1, 1)'
    );

    Schema::grantRights();

    return true;
}

function plugin_sanamassets_uninstall()
{
    // Data is kept on purpose. See the header comment.
    Schema::revokeRights();

    return true;
}
