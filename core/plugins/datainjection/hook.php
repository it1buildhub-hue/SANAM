<?php

/**
 * -------------------------------------------------------------------------
 * DataInjection plugin for SANAM
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of DataInjection.
 *
 * DataInjection is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * DataInjection is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with DataInjection. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2007-2023 by DataInjection plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      #
 * -------------------------------------------------------------------------
 */

use function Safe\mkdir;

function plugin_datainjection_install()
{
    /** @var DBmysql $DB */
    global $DB;

    include_once Plugin::getPhpDir('datainjection') . "/inc/profile.class.php";

    $migration = new Migration(PLUGIN_DATAINJECTION_VERSION);

    $default_charset = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

    switch (plugin_datainjection_needUpdateOrInstall()) {
        case -1:
            // Migrations from version 2.2.0+
            plugin_datainjection_update220_230();
            plugin_datainjection_upgrade23_240($migration);
            plugin_datainjection_migration_24_250($migration);
            plugin_datainjection_migration_251_252($migration);
            plugin_datainjection_migration_264_270($migration);
            plugin_datainjection_migration_2121_2122($migration);
            plugin_datainjection_migration_2141_2150($migration);
            plugin_datainjection_migration_2158_2159($migration);
            break;

        case 0:
            // Plugin installation
            $query = "CREATE TABLE IF NOT EXISTS `sanam_plugin_datainjection_models` (
                     `id` int {$default_key_sign} NOT NULL auto_increment,
                     `name` varchar(255) NOT NULL,
                     `comment` text NULL,
                     `date_mod` timestamp NULL DEFAULT NULL,
                     `date_creation` timestamp NULL DEFAULT NULL,
                     `filetype` varchar(255) NOT NULL default 'csv',
                     `itemtype` varchar(255) NOT NULL default '',
                     `entities_id` int {$default_key_sign} NOT NULL default '0',
                     `behavior_add` tinyint NOT NULL default '1',
                     `behavior_update` tinyint NOT NULL default '0',
                     `can_add_dropdown` tinyint NOT NULL default '0',
                     `can_overwrite_if_not_empty` int NOT NULL default '1',
                     `is_private` tinyint NOT NULL default '1',
                     `is_recursive` tinyint NOT NULL default '0',
                     `users_id` int {$default_key_sign} NOT NULL,
                     `date_format` varchar(11) NOT NULL default 'yyyy-mm-dd',
                     `float_format` tinyint NOT NULL DEFAULT '0',
                     `port_unicity` tinyint NOT NULL DEFAULT '0',
                     `step` int NOT NULL DEFAULT '0',
                     `replace_multiline_value` tinyint NOT NULL DEFAULT '0',
                     PRIMARY KEY  (`id`)
                   ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);

            $query = "CREATE TABLE IF NOT EXISTS `sanam_plugin_datainjection_modelcsvs` (
                     `id` int {$default_key_sign} NOT NULL auto_increment,
                     `models_id` int {$default_key_sign} NOT NULL,
                     `itemtype` varchar(255) NOT NULL default '',
                     `delimiter` varchar(1) NOT NULL default ';',
                     `is_header_present` tinyint NOT NULL default '1',
                     PRIMARY KEY  (`ID`)
                   ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);

            $query = "CREATE TABLE IF NOT EXISTS `sanam_plugin_datainjection_mappings` (
                     `id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                     `models_id` INT {$default_key_sign} NOT NULL ,
                     `itemtype` varchar(255) NOT NULL default '',
                     `rank` INT NOT NULL ,
                     `name` VARCHAR( 255 ) NOT NULL ,
                     `value` VARCHAR( 255 ) NOT NULL ,
                     `is_mandatory` TINYINT NOT NULL DEFAULT '0'
                   ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);

            $query = "CREATE TABLE IF NOT EXISTS `sanam_plugin_datainjection_infos` (
                     `id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                     `models_id` INT {$default_key_sign} NOT NULL ,
                     `itemtype` varchar(255) NOT NULL default '',
                     `value` VARCHAR( 255 ) NOT NULL ,
                     `is_mandatory` TINYINT NOT NULL DEFAULT '0'
                   ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query);

            if (!is_dir(PLUGIN_DATAINJECTION_UPLOAD_DIR)) {
                @ mkdir(PLUGIN_DATAINJECTION_UPLOAD_DIR);
            }

            PluginDatainjectionProfile::createFirstAccess($_SESSION["sanamactiveprofile"]["id"]);

            // Force reload of session profile rights
            Session::reloadCurrentProfile();
            break;

        case 1:
            // Migrations from version prior to 2.2.0

            //When updating, check if the upload folder is already present
            if (!is_dir(PLUGIN_DATAINJECTION_UPLOAD_DIR)) {
                @ mkdir(PLUGIN_DATAINJECTION_UPLOAD_DIR);
            }

            //Old temporary directory, needs to be removed !
            if (is_dir(SANAM_PLUGIN_DOC_DIR . "/data_injection/")) {
                Toolbox::deleteDir(SANAM_PLUGIN_DOC_DIR . "/data_injection/");
            }

            if (
                $DB->tableExists("sanam_plugin_data_injection_models")
                && !$DB->fieldExists("sanam_plugin_data_injection_models", "recursive")
            ) {
                // Update
                plugin_datainjection_update131_14();
            }

            if (
                $DB->tableExists("sanam_plugin_data_injection_models")
                && !$DB->fieldExists("sanam_plugin_data_injection_models", "port_unicity")
            ) {
                $migration->addField('sanam_plugin_data_injection_models', 'port_unicity', 'bool');
                $migration->executeMigration();
            }

            if (!$DB->tableExists("sanam_plugin_datainjection_models")) {
                plugin_datainjection_update15_170();
            }

            if (!$DB->tableExists("sanam_plugin_datainjection_modelcsvs")) {
                plugin_datainjection_update170_20();
            }

            plugin_datainjection_update210_220();

            plugin_datainjection_update220_230();

            plugin_datainjection_upgrade23_240($migration);

            plugin_datainjection_migration_24_250($migration);

            plugin_datainjection_migration_251_252($migration);

            plugin_datainjection_migration_264_270($migration);
            plugin_datainjection_migration_290_2100($migration);
            plugin_datainjection_migration_2121_2122($migration);
            plugin_datainjection_migration_2141_2150($migration);
            plugin_datainjection_migration_2158_2159($migration);
            break;

        default:
            break;
    }

    return true;
}


function plugin_datainjection_uninstall()
{
    /** @var DBmysql $DB */
    global $DB;

    $tables = ["sanam_plugin_datainjection_models",
        "sanam_plugin_datainjection_modelcsvs",
        "sanam_plugin_datainjection_mappings",
        "sanam_plugin_datainjection_infos",
        "sanam_plugin_datainjection_filetype",
        "sanam_plugin_datainjection_profiles",
    ];

    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQuery("DROP TABLE IF EXISTS `" . $table . "`");
        }
    }

    // Remove plugin rights from all profiles
    $profileRight = new ProfileRight();
    foreach (PluginDatainjectionProfile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    if (is_dir(PLUGIN_DATAINJECTION_UPLOAD_DIR)) {
        Toolbox::deleteDir(PLUGIN_DATAINJECTION_UPLOAD_DIR);
    }

    plugin_init_datainjection();
    return true;
}

function plugin_datainjection_migration_2141_2150(Migration $migration)
{
    $migration->addField(
        'sanam_plugin_datainjection_models',
        'replace_multiline_value',
        'bool',
    );

    $migration->executeMigration();
}

function plugin_datainjection_migration_2158_2159(Migration $migration)
{
    $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

    // Fix remaining legacy data (old schema stored private models with `entities_id = -1`).
    // Must run *before* the changeField() below: the column is converted to
    // `unsigned`, which would mangle the `-1` values before this cleanup could match them.
    if (countElementsInTable("sanam_plugin_datainjection_models", ['entities_id' => -1])) {
        $migration->addPreQuery(
            "UPDATE `sanam_plugin_datainjection_models`
                SET `is_private` = '1', `entities_id` = '0', `is_recursive` = '1'
              WHERE `entities_id` = '-1'",
        );
    }

    // Fix the negative column default inherited from the old schema. Databases installed
    // before `plugin_datainjection_update170_20()` kept `entities_id ... default '-1'`,
    // which prevents SANAM 11 `migration:unsigned_keys` from converting the column to unsigned.
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'entities_id',
        'entities_id',
        sprintf("int %s NOT NULL default '0'", $default_key_sign),
    );

    $migration->executeMigration();
}

function plugin_datainjection_migration_2121_2122(Migration $migration)
{
    //remove useless field
    $migration->dropField("sanam_plugin_datainjection_models", "perform_network_connection");

    //remove related display pref
    $migration->updateDisplayPrefs([], ["PluginDatainjectionModel" => [9]]);
    $migration->executeMigration();
}

function plugin_datainjection_migration_290_2100(Migration $migration)
{
    /** @var DBmysql $DB */
    global $DB;

    $migration->addPostQuery(
        $DB->buildUpdate(
            'sanam_plugin_datainjection_mappings',
            [
                'value' => 'date_creation',
            ],
            [
                'itemtype' => 'KnowbaseItem',
                'value'    => 'date',
            ],
        ),
        'Changing "date" field of "KnowbaseItem" to "date_creation".',
    );

    $migration->executeMigration();
}


function plugin_datainjection_migration_264_270(Migration $migration)
{
    /** @var DBmysql $DB */
    global $DB;

    $migration->addPostQuery(
        $DB->buildUpdate(
            'sanam_plugin_datainjection_mappings',
            [
                'value' => 'licenseid',
            ],
            [
                'itemtype' => 'Item_OperatingSystem',
                'value'    => 'license_id',
            ],
        ),
        'Changing "license_id" field of "Item_OperatingSystem" to "licenseid".',
    );

    $migration->executeMigration();
}


function plugin_datainjection_migration_251_252(Migration $migration)
{
    /** @var DBmysql $DB */
    global $DB;

    if (
        $DB->tableExists('sanam_plugin_datainjection_models')
        && $DB->fieldExists('sanam_plugin_datainjection_models', 'date_mod')
    ) {
        $migration->changeField(
            'sanam_plugin_datainjection_models',
            'date_mod',
            'date_mod',
            'timestamp NULL DEFAULT NULL',
        );
        $migration->migrationOneTable('sanam_plugin_datainjection_models');
    }

    $migration->executeMigration();
}

function plugin_datainjection_migration_24_250(Migration $migration)
{
    /** @var DBmysql $DB */
    global $DB;

    if (
        $DB->tableExists('sanam_plugin_datainjection_models')
        && !$DB->fieldExists('sanam_plugin_datainjection_models', 'date_creation')
    ) {
        $migration->addField('sanam_plugin_datainjection_models', 'date_creation', 'timestamp');
        $migration->addKey('sanam_plugin_datainjection_models', 'date_creation');
        $migration->migrationOneTable('sanam_plugin_datainjection_models');
    }

    //Migrate OSes infos
    //TODO use DB->update in 9.3
    $query = "UPDATE `sanam_plugin_datainjection_mappings`
             SET `itemtype`='Item_OperatingSystem'
             WHERE `itemtype`='Computer'
                AND `value` IN (
                   'license_id', 'license_number', 'operatingsystemservicepacks_id',
                   'operatingsystems_id', 'operatingsystemversions_id',
                   'operatingsystemarchitectures_id', 'operatingsystemkernels_id',
                   'operatingsystemkernelversions_id', 'operatingsystemeditions_id'
                )";
    $DB->doQuery($query);

    $migration->executeMigration();
}

function plugin_datainjection_upgrade23_240(Migration $migration)
{
    /** @var DBmysql $DB */
    global $DB;

    if ($DB->tableExists('sanam_plugin_datainjection_profiles')) {
        if ($DB->fieldExists('sanam_plugin_datainjection_profiles', 'ID')) {
            $migration->changeField('sanam_plugin_datainjection_profiles', 'ID', 'id', 'autoincrement');
            $migration->migrationOneTable('sanam_plugin_datainjection_profiles');
        }

        PluginDatainjectionProfile::migrateProfiles();

        //Drop profile table : no use anymore !
        $migration->dropTable('sanam_plugin_datainjection_profiles');
    }

    $migration->executeMigration();
}

function plugin_datainjection_update131_14()
{
    /** @var DBmysql $DB */
    global $DB;

    $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $migration = new Migration('1.4');

    $migration->addField(
        'sanam_plugin_data_injection_models',
        'float_format',
        'bool',
    );

    //Template recursivity : need standardize names in order to use privatePublicSwitch
    $migration->changeField(
        'sanam_plugin_data_injection_models',
        'user_id',
        'FK_users',
        sprintf("int %s NOT NULL default '0'", $default_key_sign),
    );
    $migration->changeField(
        'sanam_plugin_data_injection_models',
        'public',
        'private',
        'bool',
    );

    $migration->migrationOneTable('sanam_plugin_data_injection_models');

    $sql = "UPDATE `sanam_plugin_data_injection_models`
           SET `FK_entities` = '-1',
               `private` = '1'
           WHERE `private` = '0'";
    $DB->doQuery($sql);

    $sql = "UPDATE `sanam_plugin_data_injection_models`
           SET `private` = '0'
           WHERE `private` = '1'
                AND `FK_entities` > '0'";
    $DB->doQuery($sql);

    $migration->addField(
        'sanam_plugin_data_injection_models',
        'recursive',
        'bool',
    );

    $sql = "UPDATE `sanam_plugin_data_injection_profiles`
           SET `create_model` = `use_model`
           WHERE `create_model` IS NULL";
    $DB->doQuery($sql);

    $migration->dropField('sanam_plugin_data_injection_profiles', 'use_model');
    $migration->changeField(
        'sanam_plugin_data_injection_profiles',
        'create_model',
        'model',
        'char',
    );

    $migration->executeMigration();
}


function plugin_datainjection_update15_170()
{
    /** @var DBmysql $DB */
    global $DB;

    $tables = ["sanam_plugin_data_injection_models"     => "sanam_plugin_datainjection_models",
        "sanam_plugin_data_injection_models_csv" => "sanam_plugin_datainjection_models_csv",
        "sanam_plugin_data_injection_mappings"   => "sanam_plugin_datainjection_mappings",
        "sanam_plugin_data_injection_infos"      => "sanam_plugin_datainjection_infos",
        "sanam_plugin_data_injection_filetype"   => "sanam_plugin_datainjection_filetype",
        "sanam_plugin_data_injection_profiles"   => "sanam_plugin_datainjection_profiles",
    ];

    foreach ($tables as $oldname => $newname) {
        $query = "RENAME TABLE IF EXISTS `" . $oldname . "` TO `" . $newname . "`";
        $DB->doQuery($query);
    }
}


function plugin_datainjection_update170_20()
{
    /** @var DBmysql $DB */
    global $DB;

    $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $migration = new Migration('2.0');

    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'ID',
        'id',
        'autoincrement',
    );
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'type',
        'filetype',
        'string',
        ['value' => 'csv'],
    );
    $migration->addField('sanam_plugin_datainjection_models', 'step', 'integer');
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'comments',
        'comment',
        'text',
    );
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'device_type',
        'itemtype',
        'string',
        ['value' => ''],
    );
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'FK_entities',
        'entities_id',
        sprintf("int %s NOT NULL default '0'", $default_key_sign),
    );
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'private',
        'is_private',
        'bool',
    );
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'FK_users',
        'users_id',
        sprintf("int %s NOT NULL default '0'", $default_key_sign),
    );
    $migration->changeField(
        'sanam_plugin_datainjection_models',
        'recursive',
        'is_recursive',
        'bool',
    );

    $migration->migrationOneTable('sanam_plugin_datainjection_models');

    $query = "UPDATE `sanam_plugin_datainjection_models`
              SET `step` = '5'";
    $DB->doQuery($query);

    $query = "UPDATE `sanam_plugin_datainjection_models`
              SET `filetype` = 'csv'";
    $DB->doQuery($query);

    $migration->dropTable('sanam_plugin_datainjection_filetype');

    $migration->renameTable(
        'sanam_plugin_datainjection_models_csv',
        'sanam_plugin_datainjection_modelcsvs',
    );

    $migration->changeField(
        'sanam_plugin_datainjection_modelcsvs',
        'model_id',
        'models_id',
        sprintf("int %s NOT NULL default '0'", $default_key_sign),
    );
    $migration->changeField(
        'sanam_plugin_datainjection_modelcsvs',
        'device_type',
        'itemtype',
        'string',
        ['value' => ''],
    );
    $migration->changeField(
        'sanam_plugin_datainjection_modelcsvs',
        'header_present',
        'is_header_present',
        'bool',
        ['value' => 1],
    );

    $migration->changeField(
        'sanam_plugin_datainjection_mappings',
        'mandatory',
        'is_mandatory',
        'bool',
    );
    $migration->changeField(
        'sanam_plugin_datainjection_mappings',
        'type',
        'itemtype',
        'string',
        ['value' => ''],
    );
    $migration->changeField(
        'sanam_plugin_datainjection_mappings',
        'model_id',
        'models_id',
        sprintf("int %s NOT NULL default '0'", $default_key_sign),
    );

    $migration->changeField(
        'sanam_plugin_datainjection_infos',
        'type',
        'itemtype',
        'string',
        ['value' => ''],
    );
    $migration->changeField(
        'sanam_plugin_datainjection_infos',
        'model_id',
        'models_id',
        sprintf("int %s NOT NULL default '0'", $default_key_sign),
    );
    $migration->changeField(
        'sanam_plugin_datainjection_infos',
        'mandatory',
        'is_mandatory',
        'bool',
    );

    $sanamtables = ['sanam_plugin_datainjection_models',
        'sanam_plugin_datainjection_mappings',
        'sanam_plugin_datainjection_modelcsvs',
        'sanam_plugin_datainjection_infos',
        'sanam_plugin_datainjection_profiles',
    ];

    foreach ($sanamtables as $table) {
        $migration->changeField($table, 'ID', 'id', 'autoincrement');
    }

    $migration->migrationOneTable('sanam_plugin_datainjection_mappings');
    $migration->migrationOneTable('sanam_plugin_datainjection_infos');
    $migration->migrationOneTable('sanam_plugin_datainjection_modelcsvs');

    $query = "UPDATE `sanam_plugin_datainjection_mappings`
             SET `itemtype` = 'none' ,
                 `value`='none'
             WHERE `itemtype` = '-1'";
    $DB->doQuery($query);

    $migration->migrationOneTable('sanam_plugin_datainjection_infos');
    $query = "UPDATE `sanam_plugin_datainjection_infos`
             SET `itemtype` = 'none', `value` = 'none'
             WHERE `itemtype` = '-1'";
    $DB->doQuery($query);

    $foreignkeys = [
        'assign' => [
            [
                'to' => 'users_id_assign',
                'tables' => [
                    'sanam_tickets',
                ],
            ],
        ],
        'assign_group' => [
            [
                'to' => 'groups_id_assign',
                'tables' => [
                    'sanam_tickets',
                ],
            ],
        ],
        'assign_ent' => [
            [
                'to' => 'suppliers_id_assign',
                'tables' => [
                    'sanam_tickets',
                ],
            ],
        ],
        'auth_method' => [
            [
                'to' => 'authtype',
                'noindex' => [
                    'sanam_users',
                ],
                'tables' => [
                    'sanam_users',
                ],
            ],
        ],
        'author' => [
            [
                'to' => 'users_id',
                'tables' => [
                    'sanam_itilfollowups',
                    'sanam_knowbaseitems',
                    'sanam_tickets',
                ],
            ],
        ],
        'auto_update' => [
            [
                'to' => 'autoupdatesystems_id',
                'tables' => [
                    'sanam_computers',
                ],
            ],
        ],
        'budget' => [
            [
                'to' => 'budgets_id',
                'tables' => [
                    'sanam_infocoms',
                ],
            ],
        ],
        'buy_version' => [
            [
                'to' => 'softwareversions_id_buy',
                'tables' => [
                    'sanam_softwarelicenses',
                ],
            ],
        ],
        'category' => [
            [
                'to' => 'ticketcategories_id',
                'tables' => [
                    'sanam_tickets',
                ],
            ],
            [
                'to' => 'softwarecategories_id',
                'tables' => [
                    'sanam_softwares',
                ],
            ],
        ],
        'categoryID' => [
            [
                'to' => 'knowbaseitemcategories_id',
                'tables' => [
                    'sanam_knowbaseitems',
                ],
            ],
        ],
        'cID' => [
            [
                'to' => 'computers_id',
                'tables' => [
                    'sanam_computers_softwareversions',
                ],
            ],
        ],
        'computer' => [
            [
                'to' => 'items_id',
                'tables' => [
                    'sanam_tickets',
                ],
            ],
        ],
        'computer_id' => [
            [
                'to' => 'computers_id',
                'tables' => [
                    'sanam_registrykeys',
                ],
            ],
        ],
        'contract_type' => [
            [
                'to' => 'contracttypes_id',
                'tables' => [
                    'sanam_contracts',
                ],
            ],
        ],
        'default_rubdoc_tracking' => [
            [
                'to' => 'documentcategories_id_forticket',
                'tables' => [
                    'sanam_configs',
                ],
                'comments' => [
                    'sanam_configs' => 'default category for documents added with a ticket',
                ],
            ],
        ],
        'device_type' => [
            [
                'to' => 'itemtype',
                'tables' => [
                    'sanam_alerts',
                    'sanam_contracts_items',
                    'sanam_documents_items',
                    'sanam_infocoms',
                    'sanam_bookmarks',
                    'sanam_bookmarks_users',
                    'sanam_links_itemtypes',
                    'sanam_networkports',
                    'sanam_reservationitems',
                    'sanam_tickets',
                ],
            ],
        ],
        'domain' => [
            [
                'to' => 'domains_id',
                'tables' => [
                    'sanam_computers',
                    'sanam_networkequipments',
                    'sanam_printers',
                ],
            ],
        ],
        'end1' => [
            [
                'to' => 'items_id',
                'tables' => [
                    'sanam_computers_items',
                ],
                'comments' => [
                    'sanam_computers_items' => 'RELATION to various table, according to itemtype (ID]',
                ],
            ],
            [
                'to' => 'networkports_id_1',
                'tables' => [
                    'sanam_networkports_networkports',
                ],
            ],
        ],
        'end2' => [
            [
                'to' => 'computers_id',
                'tables' => [
                    'sanam_computers_items',
                ],
            ],
            [
                'to' => 'networkports_id_2',
                'tables' => [
                    'sanam_networkports_networkports',
                ],
            ],
        ],
        'firmware' => [
            [
                'to' => 'networkequipmentfirmwares_id',
                'tables' => [
                    'sanam_networkequipments',
                ],
            ],
        ],
        'FK_bookmark' => [
            [
                'to' => 'bookmarks_id',
                'tables' => [
                    'sanam_bookmarks_users',
                ],
            ],
        ],
        'FK_computers' => [
            [
                'to' => 'computers_id',
                'tables' => [
                    'sanam_computerdisks',
                    'sanam_softwarelicenses',
                ],
            ],
        ],
        'FK_contact' => [
            [
                'to' => 'contacts_id',
                'tables' => [
                    'sanam_contacts_suppliers',
                ],
            ],
        ],
        'FK_contract' => [
            [
                'to' => 'contracts_id',
                'tables' => [
                    'sanam_contracts_suppliers',
                    'sanam_contracts_items',
                ],
            ],
        ],
        'FK_device' => [
            [
                'to' => 'items_id',
                'tables' => [
                    'sanam_alerts',
                    'sanam_contracts_items',
                    'sanam_documents_items',
                    'sanam_infocoms',
                ],
            ],
        ],
        'FK_doc' => [
            [
                'to' => 'documents_id',
                'tables' => [
                    'sanam_documents_items',
                ],
            ],
        ],
        'manufacturer' => [
            [
                'to' => 'suppliers_id',
                'tables' => [
                    'sanam_contacts_suppliers',
                    'sanam_contracts_suppliers',
                    'sanam_infocoms',
                ],
            ],
            [
                'to' => 'manufacturers_id',
                'tables' => [
                    'sanam_cartridgeitems',
                    'sanam_computers',
                    'sanam_consumableitems',
                    'sanam_devicecases',
                    'sanam_devicecontrols',
                    'sanam_devicedrives',
                    'sanam_devicegraphiccards',
                    'sanam_deviceharddrives',
                    'sanam_devicenetworkcards',
                    'sanam_devicemotherboards',
                    'sanam_devicepcis',
                    'sanam_devicepowersupplies',
                    'sanam_deviceprocessors',
                    'sanam_devicememories',
                    'sanam_devicesoundcards',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_softwares',
                ],
            ],
        ],
        'FK_entities' => [
            [
                'to' => 'entities_id',
                'tables' => [
                    'sanam_bookmarks',
                    'sanam_cartridgeitems',
                    'sanam_computers',
                    'sanam_consumableitems',
                    'sanam_contacts',
                    'sanam_contracts',
                    'sanam_documents',
                    'sanam_locations',
                    'sanam_netpoints',
                    'sanam_suppliers',
                    'sanam_entitydatas',
                    'sanam_groups',
                    'sanam_knowbaseitems',
                    'sanam_links',
                    'sanam_mailcollectors',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_reminders',
                    'sanam_rules',
                    'sanam_softwares',
                    'sanam_softwarelicenses',
                    'sanam_softwareversions',
                    'sanam_tickets',
                    'sanam_users',
                    'sanam_profiles_users',
                ],
                'default' => [
                    'sanam_bookmarks' => "-1",
                ],
            ],
        ],
        'FK_filesystems' => [
            [
                'to' => 'filesystems_id',
                'tables' => [
                    'sanam_computerdisks',
                ],
            ],
        ],
        'FK_sanam_cartridges_type' => [
            [
                'to' => 'cartridgeitems_id',
                'tables' => [
                    'sanam_cartridges',
                    'sanam_cartridges_printermodels',
                ],
            ],
        ],
        'FK_sanam_consumables_type' => [
            [
                'to' => 'consumableitems_id',
                'tables' => [
                    'sanam_consumables',
                ],
            ],
        ],
        'FK_sanam_dropdown_model_printers' => [
            [
                'to' => 'printermodels_id',
                'tables' => [
                    'sanam_cartridges_printermodels',
                ],
            ],
        ],
        'FK_sanam_printers' => [
            [
                'to' => 'printers_id',
                'tables' => [
                    'sanam_cartridges',
                ],
            ],
        ],
        'FK_group' => [
            [
                'to' => 'groups_id',
                'tables' => [
                    'sanam_tickets',
                ],
            ],
        ],
        'FK_groups' => [
            [
                'to' => 'groups_id',
                'tables' => [
                    'sanam_computers',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_softwares',
                    'sanam_groups_users',
                ],
            ],
        ],
        'FK_interface' => [
            [
                'to' => 'interfacetypes_id',
                'tables' => [
                    'sanam_devicegraphiccards',
                ],
            ],
        ],
        'FK_item' => [
            [
                'to' => 'items_id',
                'tables' => [
                    'sanam_mailingsettings',
                ],
            ],
        ],
        'FK_links' => [
            [
                'to' => 'links_id',
                'tables' => [
                    'sanam_links_itemtypes',
                ],
            ],
        ],
        'FK_port' => [
            [
                'to' => 'networkports_id',
                'tables' => [
                    'sanam_networkports_vlans',
                ],
            ],
        ],
        'FK_profiles' => [
            [
                'to' => 'profiles_id',
                'tables' => [
                    'sanam_profiles_users',
                    'sanam_users',
                ],
            ],
        ],
        'FK_users' => [
            [
                'to' => 'users_id',
                'tables' => [
                    'sanam_bookmarks',
                    'sanam_displaypreferences',
                    'sanam_documents',
                    'sanam_groups',
                    'sanam_reminders',
                    'sanam_bookmarks_users',
                    'sanam_groups_users',
                    'sanam_profiles_users',
                    'sanam_computers',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_softwares',
                ],
            ],
        ],
        'FK_vlan' => [
            [
                'to' => 'vlans_id',
                'tables' => [
                    'sanam_networkports_vlans',
                ],
            ],
        ],
        'sanam_id' => [
            [
                'to' => 'computers_id',
                'tables' => [
                    'sanam_ocslinks',
                ],
            ],
        ],
        'id_assign' => [
            [
                'to' => 'users_id',
                'tables' => [
                    'sanam_ticketplannings',
                ],
            ],
        ],
        'id_auth' => [
            [
                'to' => 'auths_id',
                'tables' => [
                    'sanam_users',
                ],
            ],
        ],
        'id_device' => [
            [
                'to' => 'items_id',
                'tables' => [
                    'sanam_reservationitems',
                ],
            ],
        ],
        'id_item' => [
            [
                'to' => 'reservationitems_id',
                'tables' => [
                    'sanam_reservations',
                ],
            ],
        ],
        'id_user' => [
            [
                'to' => 'users_id',
                'tables' => [
                    'sanam_consumables',
                    'sanam_reservations',
                ],
            ],
        ],
        'iface' => [
            [
                'to' => 'networkinterfaces_id',
                'tables' => [
                    'sanam_networkports',
                ],
            ],
        ],
        'interface' => [
            [
                'to' => 'interfacetypes_id',
                'tables' => [
                    'sanam_devicecontrols',
                    'sanam_deviceharddrives',
                    'sanam_devicedrives',
                ],
            ],
        ],
        'location' => [
            [
                'to' => 'locations_id',
                'tables' => [
                    'sanam_cartridgeitems',
                    'sanam_computers',
                    'sanam_consumableitems',
                    'sanam_netpoints',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_users',
                    'sanam_softwares',
                ],
            ],
        ],
        'model' => [
            [
                'to' => 'computermodels_id',
                'tables' => [
                    'sanam_computers',
                ],
            ],
            [
                'to' => 'monitormodels_id',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
            [
                'to' => 'networkequipmentmodels_id',
                'tables' => [
                    'sanam_networkequipments',
                ],
            ],
            [
                'to' => 'peripheralmodels_id',
                'tables' => [
                    'sanam_peripherals',
                ],
            ],
            [
                'to' => 'phonemodels_id',
                'tables' => [
                    'sanam_phones',
                ],
            ],
            [
                'to' => 'printermodels_id',
                'tables' => [
                    'sanam_printers',
                ],
            ],
        ],
        'netpoint' => [
            [
                'to' => 'netpoints_id',
                'tables' => [
                    'sanam_networkports',
                ],
            ],
        ],
        'network' => [
            [
                'to' => 'networks_id',
                'tables' => [
                    'sanam_computers',
                    'sanam_networkequipments',
                    'sanam_printers',
                ],
            ],
        ],
        'on_device' => [
            [
                'to' => 'items_id',
                'tables' => [
                    'sanam_networkports',
                ],
            ],
        ],
        'os' => [
            [
                'to' => 'operatingsystems_id',
                'tables' => [
                    'sanam_computers',
                ],
            ],
        ],
        'os_license_id' => [
            [
                'to' => 'os_licenseid',
                'tables' => [
                    'sanam_computers',
                ],
            ],
        ],
        'os_version' => [
            [
                'to' => 'operatingsystemversions_id',
                'tables' => [
                    'sanam_computers',
                ],
            ],
        ],
        'parentID' => [
            [
                'to' => 'knowbaseitemcategories_id',
                'tables' => [
                    'sanam_knowbaseitemcategories',
                ],
            ],
            [
                'to' => 'locations_id',
                'tables' => [
                    'sanam_locations',
                ],
            ],
            [
                'to' => 'ticketcategories_id',
                'tables' => [
                    'sanam_ticketcategories',
                ],
            ],
            [
                'to' => 'entities_id',
                'tables' => [
                    'sanam_entities',
                ],
            ],
        ],
        'platform' => [
            [
                'to' => 'operatingsystems_id',
                'tables' => [
                    'sanam_softwares',
                ],
            ],
        ],
        'power' => [
            [
                'to' => 'phonepowersupplies_id',
                'tables' => [
                    'sanam_phones',
                ],
            ],
        ],
        'recipient' => [
            [
                'to' => 'users_id_recipient',
                'tables' => [
                    'sanam_tickets',
                ],
            ],
        ],
        'rubrique' => [
            [
                'to' => 'documentcategories_id',
                'tables' => [
                    'sanam_documents',
                ],
            ],
        ],
        'sID' => [
            [
                'to' => 'softwares_id',
                'tables' => [
                    'sanam_softwarelicenses',
                    'sanam_softwareversions',
                ],
            ],
        ],
        'state' => [
            [
                'to' => 'states_id',
                'tables' => [
                    'sanam_computers',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_softwareversions',
                ],
            ],
        ],
        'tech_num' => [
            [
                'to' => 'users_id_tech',
                'tables' => [
                    'sanam_cartridgeitems',
                    'sanam_computers',
                    'sanam_consumableitems',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_softwares',
                ],
            ],
        ],
        'title' => [
            [
                'to' => 'usertitles_id',
                'tables' => [
                    'sanam_users',
                ],
            ],
        ],
        'type' => [
            [
                'to' => 'cartridgeitemtypes_id',
                'tables' => [
                    'sanam_cartridgeitems',
                ],
            ],
            [
                'to' => 'computertypes_id',
                'tables' => [
                    'sanam_computers',
                ],
            ],
            [
                'to' => 'consumableitemtypes_id',
                'tables' => [
                    'sanam_consumableitems',
                ],
            ],
            [
                'to' => 'contacttypes_id',
                'tables' => [
                    'sanam_contacts',
                ],
            ],
            [
                'to' => 'devicecasetypes_id',
                'tables' => [
                    'sanam_devicecases',
                ],
            ],
            [
                'to' => 'devicememorytypes_id',
                'tables' => [
                    'sanam_devicememories',
                ],
            ],
            [
                'to' => 'suppliertypes_id',
                'tables' => [
                    'sanam_suppliers',
                ],
            ],
            [
                'to' => 'monitortypes_id',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
            [
                'to' => 'networkequipmenttypes_id',
                'tables' => [
                    'sanam_networkequipments',
                ],
            ],
            [
                'to' => 'peripheraltypes_id',
                'tables' => [
                    'sanam_peripherals',
                ],
            ],
            [
                'to' => 'phonetypes_id',
                'tables' => [
                    'sanam_phones',
                ],
            ],
            [
                'to' => 'printertypes_id',
                'tables' => [
                    'sanam_printers',
                ],
            ],
            [
                'to' => 'softwarelicensetypes_id',
                'tables' => [
                    'sanam_softwarelicenses',
                ],
            ],
            [
                'to' => 'usercategories_id',
                'tables' => [
                    'sanam_users',
                ],
            ],
            [
                'to' => 'itemtype',
                'tables' => [
                    'sanam_computers_items',
                    'sanam_displaypreferences',
                ],
            ],
        ],
        'update_software' => [
            [
                'to' => 'softwares_id',
                'tables' => [
                    'sanam_softwares',
                ],
            ],
        ],
        'use_version' => [
            [
                'to' => 'softwareversions_id_use',
                'tables' => [
                    'sanam_softwarelicenses',
                ],
            ],
        ],
        'vID' => [
            [
                'to' => 'softwareversions_id',
                'tables' => [
                    'sanam_computers_softwareversions',
                ],
            ],
        ],
        'conpta_num' => [
            [
                'to' => 'accounting_number',
                'tables' => [
                    'sanam_contracts',
                ],
            ],
        ],
        'num_commande' => [
            [
                'to' => 'order_number',
                'tables' => [
                    'sanam_infocoms',
                ],
            ],
        ],
        'bon_livraison' => [
            [
                'to' => 'delivery_number',
                'tables' => [
                    'sanam_infocoms',
                ],
            ],
        ],
        'num_immo' => [
            [
                'to' => 'immo_number',
                'tables' => [
                    'sanam_infocoms',
                ],
            ],
        ],
        'facture' => [
            [
                'to' => 'bill',
                'tables' => [
                    'sanam_infocoms',
                ],
            ],
        ],
        'amort_time' => [
            [
                'to' => 'sink_time',
                'tables' => [
                    'sanam_infocoms',
                ],
            ],
        ],
        'amort_type' => [
            [
                'to' => 'sink_type',
                'tables' => [
                    'sanam_infocoms',
                ],
            ],
        ],
        'ifmac' => [
            [
                'to' => 'mac',
                'tables' => [
                    'sanam_networkequipments',
                ],
            ],
        ],
        'ifaddr' => [
            [
                'to' => 'ip',
                'tables' => [
                    'sanam_networkequipments',
                    'sanam_networkports',
                ],
            ],
        ],
        'ramSize' => [
            [
                'to' => 'memory_size',
                'tables' => [
                    'sanam_printers',
                ],
            ],
        ],
        'facturation' => [
            [
                'to' => 'billing',
                'tables' => [
                    'sanam_contracts',
                ],
            ],
        ],
        'monday' => [
            [
                'to' => 'use_monday',
                'tables' => [
                    'sanam_contracts',
                ],
            ],
        ],
        'saturday' => [
            [
                'to' => 'use_saturday',
                'tables' => [
                    'sanam_contracts',
                ],
            ],
        ],
        'recursive' => [
            [
                'to' => 'is_recursive',
                'tables' => [
                    'sanam_networkequipments',
                    'sanam_groups',
                    'sanam_contracts',
                    'sanam_contacts',
                    'sanam_suppliers',
                    'sanam_printers',
                    'sanam_softwares',
                    'sanam_softwareversions',
                    'sanam_softwarelicences',
                ],
            ],
        ],
        'faq' => [
            [
                'to' => 'is_faq',
                'tables' => [
                    'sanam_knowbaseitems',
                ],
            ],
        ],
        'flags_micro' => [
            [
                'to' => 'have_micro',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
        ],
        'flags_speaker' => [
            [
                'to' => 'have_speaker',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
        ],
        'flags_subd' => [
            [
                'to' => 'have_subd',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
        ],
        'flags_bnc' => [
            [
                'to' => 'have_bnc',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
        ],
        'flags_dvi' => [
            [
                'to' => 'have_dvi',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
        ],
        'flags_pivot' => [
            [
                'to' => 'have_pivot',
                'tables' => [
                    'sanam_monitors',
                ],
            ],
        ],
        'flags_hp' => [
            [
                'to' => 'have_hp',
                'tables' => [
                    'sanam_phones',
                ],
            ],
        ],
        'flags_casque' => [
            [
                'to' => 'have_headset',
                'tables' => [
                    'sanam_phones',
                ],
            ],
        ],
        'flags_usb' => [
            [
                'to' => 'have_usb',
                'tables' => [
                    'sanam_printers',
                ],
            ],
        ],
        'flags_par' => [
            [
                'to' => 'have_parallel',
                'tables' => [
                    'sanam_printers',
                ],
            ],
        ],
        'flags_serial' => [
            [
                'to' => 'have_serial',
                'tables' => [
                    'sanam_printers',
                ],
            ],
        ],
        'initial_pages' => [
            [
                'to' => 'init_pages_counter',
                'tables' => [
                    'sanam_printers',
                ],
            ],
        ],
        'global' => [
            [
                'to' => 'is_global',
                'tables' => [
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_softwares',
                ],
            ],
        ],
        'template' => [
            [
                'to' => 'template_name',
                'tables' => [
                    'sanam_cartridgeitems',
                    'sanam_computers',
                    'sanam_consumableitems',
                    'sanam_devicecases',
                    'sanam_devicecontrols',
                    'sanam_devicedrives',
                    'sanam_devicegraphiccards',
                    'sanam_deviceharddrives',
                    'sanam_devicenetworkcards',
                    'sanam_devicemotherboards',
                    'sanam_devicepcis',
                    'sanam_devicepowersupplies',
                    'sanam_deviceprocessors',
                    'sanam_devicememories',
                    'sanam_devicesoundcards',
                    'sanam_monitors',
                    'sanam_networkequipments',
                    'sanam_peripherals',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_softwares',
                ],
            ],
        ],
        'comments' => [
            [
                'to' => 'comment',
                'tables' => [
                    'sanam_cartridgeitems',
                    'sanam_computers',
                    'sanam_consumableitems',
                    'sanam_contacts',
                    'sanam_contracts',
                    'sanam_documents',
                    'sanam_autoupdatesystems',
                    'sanam_budgets',
                    'sanam_cartridgeitemtypes',
                    'sanam_devicecasetypes',
                    'sanam_consumableitemtypes',
                    'sanam_contacttypes',
                    'sanam_contracttypes',
                    'sanam_domains',
                    'sanam_suppliertypes',
                    'sanam_filesystems',
                    'sanam_networkequipmentfirmwares',
                    'sanam_networkinterfaces',
                    'sanam_interfacetypes',
                    'sanam_knowbaseitemcategories',
                    'sanam_softwarelicensetypes',
                    'sanam_locations',
                    'sanam_manufacturers',
                    'sanam_computermodels',
                    'sanam_monitormodels',
                    'sanam_networkequipmentmodels',
                    'sanam_peripheralmodels',
                    'sanam_phonemodels',
                    'sanam_printermodels',
                    'sanam_netpoints',
                    'sanam_networks',
                    'sanam_operatingsystems',
                    'sanam_operatingsystemservicepacks',
                    'sanam_operatingsystemversions',
                    'sanam_phonepowersupplies',
                    'sanam_devicememorytypes',
                    'sanam_documentcategories',
                    'sanam_softwarecategories',
                    'sanam_states',
                    'sanam_ticketcategories',
                    'sanam_usertitles',
                    'sanam_usercategories',
                    'sanam_vlans',
                    'sanam_suppliers',
                    'sanam_entities',
                    'sanam_groups',
                    'sanam_infocoms',
                    'sanam_monitors',
                    'sanam_phones',
                    'sanam_printers',
                    'sanam_peripherals',
                    'sanam_networkequipments',
                    'sanam_reservationitems',
                    'sanam_rules',
                    'sanam_softwares',
                    'sanam_softwarelicenses',
                    'sanam_softwareversions',
                    'sanam_computertypes',
                    'sanam_monitortypes',
                    'sanam_networkequipmenttypes',
                    'sanam_peripheraltypes',
                    'sanam_phonetypes',
                    'sanam_printertypes',
                    'sanam_users',
                ],
            ],
        ],
        'notes' => [
            [
                'to' => 'notepad',
                'tables' => [
                    'sanam_cartridgeitems',
                    'sanam_computers',
                    'sanam_consumableitems',
                    'sanam_contacts',
                    'sanam_contracts',
                    'sanam_documents',
                    'sanam_suppliers',
                    'sanam_entitydatas',
                    'sanam_printers',
                    'sanam_monitors',
                    'sanam_phones',
                    'sanam_peripherals',
                    'sanam_networkequipments',
                    'sanam_softwares',
                ],
            ],
        ],
    ];

    $foreignkeys = Plugin::doHookFunction("plugin_datainjection_migratefields", $foreignkeys);

    $query = "SELECT `itemtype`, `value`
            FROM `sanam_plugin_datainjection_mappings`
            WHERE `itemtype` NOT IN ('none')
            GROUP BY `itemtype`,`value`";

    foreach ($DB->doQuery($query) as $data) {
        if (isset($foreignkeys[$data['value']])) {
            foreach ($foreignkeys[$data['value']] as $field_info) {
                $table = getTableForItemType($data['itemtype']);
                if (in_array($table, $field_info['tables'])) {
                    $query = "UPDATE `sanam_plugin_datainjection_mappings`
                         SET `value` = '" . $field_info['to'] . "'
                         WHERE `itemtype` = '" . $data['itemtype'] . "'
                           AND `value` = '" . $data['value'] . "'";
                    $DB->doQuery($query);
                    $query = "UPDATE `sanam_plugin_datainjection_infos`
                         SET `value` = '" . $field_info['to'] . "'
                         WHERE `itemtype` = '" . $data['itemtype'] . "'
                          AND `value` = '" . $data['value'] . "'";
                    $DB->doQuery($query);
                }
            }
        }
    }
}

function plugin_datainjection_update210_220()
{
    /** @var DBmysql $DB */
    global $DB;

    foreach (
        ['sanam_plugin_datainjection_mappings',
            'sanam_plugin_datainjection_infos',
        ] as $table
    ) {
        $move = ['TicketCategory'     => 'ITILCategory',
            'TicketSolutionType' => 'SolutionType',
        ];
        foreach ($move as $old => $new) {
            $query = "UPDATE `" . $table . "`
                   SET `itemtype` = '" . $new . "'
                   WHERE `itemtype` = '" . $old . "'";
            $DB->doQuery($query);
        }

        //emails are now dropdowns
        $query = "UPDATE `" . $table . "`
                SET `value` = 'useremails_id'
                WHERE `itemtype` = 'User'
                      AND `value` = 'email'";
        $DB->doQuery($query);
    }
}

function plugin_datainjection_update220_230()
{
    /** @var DBmysql $DB */
    global $DB;

    if (countElementsInTable("sanam_plugin_datainjection_models", ['entities_id' => -1])) {
        $query = "UPDATE `sanam_plugin_datainjection_models`
                SET `is_private` = '1',
                    `entities_id` = '0',
                    `is_recursive` = '1'
               WHERE `entities_id` = '-1'";
        $DB->doQuery($query);
    }
}


/**
 * @param string $hook_name
 * @param array $params
**/
function plugin_datainjection_loadHook($hook_name, $params = [])
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    if (!empty($params)) {
        $type = $params["type"];
        //If a plugin type is defined
        Plugin::doOneHook(
            $PLUGIN_HOOKS['plugin_types'][$type],
            'datainjection_' . $hook_name,
        );
    } elseif (isset($PLUGIN_HOOKS['plugin_types'])) {
        //Browse all plugins
        foreach ($PLUGIN_HOOKS['plugin_types'] as $name) {
            Plugin::doOneHook($name, 'datainjection_' . $hook_name);
        }
    }
}


function plugin_datainjection_needUpdateOrInstall()
{
    /** @var DBmysql $DB */
    global $DB;

    //Install plugin
    if (!$DB->tableExists('sanam_plugin_datainjection_models')) {
        return 0;
    }

    if ($DB->tableExists("sanam_plugin_datainjection_modelcsvs")) {
        return -1;
    }

    return 1;
}


/**
 * Used for filter list of models
 *
 * @param string $itemtype
**/
function plugin_datainjection_addDefaultWhere($itemtype)
{

    switch ($itemtype) {
        case 'PluginDatainjectionModel':
            $models = PluginDatainjectionModel::getModels(
                Session::getLoginUserID(),
                'name',
                $_SESSION['sanamactive_entity'],
                true,
            );
            if (count($models) > 0) {
                $tab = [];
                foreach ($models as $model) {
                    $tab[] = $model['id'];
                }

                return "`sanam_plugin_datainjection_models`.`id` IN ('" . implode("','", $tab) . "')";
            } else {
                return "1 = 0"; //no model available -> force WHERE clause to get no result
            }
            // no break
        default:
            break;
    }

    return null;
}
