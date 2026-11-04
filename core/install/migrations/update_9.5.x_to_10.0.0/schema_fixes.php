<?php

/**
 * ---------------------------------------------------------------------
 *
 * SANAM
 *
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of SANAM.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// Remove the `NOT NULL` flag of comment fields and fix collation
$tables = [
    'sanam_apiclients',
    'sanam_applianceenvironments',
    'sanam_appliances',
    'sanam_appliancetypes',
    'sanam_devicesimcards',
    'sanam_knowbaseitems_comments',
    'sanam_lines',
    'sanam_rulerightparameters',
    'sanam_ssovariables',
    'sanam_virtualmachinestates',
    'sanam_virtualmachinesystems',
    'sanam_virtualmachinetypes',
];
foreach ($tables as $table) {
    $migration->changeField($table, 'comment', 'comment', 'text');
}

// Add `DEFAULT CURRENT_TIMESTAMP` to some date fields
$tables = [
    'sanam_alerts',
    'sanam_crontasklogs',
    'sanam_notimportedemails',
];
foreach ($tables as $table) {
    $migration->changeField($table, 'date', 'date', 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP');
}

// Fix charset for sanam_notimportedemails table
$migration->addPreQuery(
    sprintf(
        'ALTER TABLE %s CONVERT TO CHARACTER SET %s COLLATE %s',
        $DB->quoteName('sanam_notimportedemails'),
        DBConnection::getDefaultCharset(),
        DBConnection::getDefaultCollation()
    )
);
// Put back `subject` type to text (charset convertion changed it from text to mediumtext)
$migration->changeField('sanam_notimportedemails', 'subject', 'subject', 'text', ['nodefault' => true]);

// Drop malformed keys
$malformed_keys = [
    'sanam_ipaddresses' => [
        'textual',
    ],
    'sanam_items_softwareversions' => [
        'is_deleted',
        'is_template',
    ],
    'sanam_registeredids' => [
        'item',
    ],
];
foreach ($malformed_keys as $table => $keys) {
    foreach ($keys as $key) {
        $migration->dropKey($table, $key);
        $migration->migrationOneTable($table);
    }
}

// Drop useless keys
$useless_keys = [
    'sanam_appliances_items' => [
        'appliances_id',
    ],
    'sanam_appliances_items_relations' => [
        'itemtype',
        'items_id',
    ],
    'sanam_certificates_items' => [
        'device',
    ],
    'sanam_changetemplatehiddenfields' => [
        'changetemplates_id',
    ],
    'sanam_changetemplatemandatoryfields' => [
        'changetemplates_id',
    ],
    'sanam_contracts_items' => [
        'FK_device',
    ],
    'sanam_dashboards_rights' => [
        'dashboards_dashboards_id',
    ],
    'sanam_domains_items' => [
        'domains_id',
        'FK_device',
    ],
    'sanam_dropdowntranslations' => [
        'typeid',
    ],
    'sanam_entities' => [
        'entities_id',
    ],
    'sanam_impactrelations' => [
        'source_asset',
    ],
    'sanam_ipaddresses_ipnetworks' => [
        'ipaddresses_id',
    ],
    'sanam_items_devicebatteries' => [
        'computers_id',
    ],
    'sanam_items_devicecases' => [
        'computers_id',
    ],
    'sanam_items_devicecontrols' => [
        'computers_id',
    ],
    'sanam_items_devicedrives' => [
        'computers_id',
    ],
    'sanam_items_devicefirmwares' => [
        'computers_id',
    ],
    'sanam_items_devicegenerics' => [
        'computers_id',
    ],
    'sanam_items_devicegraphiccards' => [
        'computers_id',
    ],
    'sanam_items_deviceharddrives' => [
        'computers_id',
    ],
    'sanam_items_devicememories' => [
        'computers_id',
    ],
    'sanam_items_devicemotherboards' => [
        'computers_id',
    ],
    'sanam_items_devicenetworkcards' => [
        'computers_id',
    ],
    'sanam_items_devicepcis' => [
        'computers_id',
    ],
    'sanam_items_devicepowersupplies' => [
        'computers_id',
    ],
    'sanam_items_deviceprocessors' => [
        'computers_id',
    ],
    'sanam_items_devicesensors' => [
        'computers_id',
    ],
    'sanam_items_devicesoundcards' => [
        'computers_id',
    ],
    'sanam_items_disks' => [
        'itemtype',
        'items_id',
    ],
    'sanam_items_operatingsystems' => [
        'items_id',
    ],
    'sanam_items_softwarelicenses' => [
        'itemtype',
        'items_id',
    ],
    'sanam_items_softwareversions' => [
        'item',
        'itemtype',
        'items_id',
    ],
    'sanam_itilfollowups' => [
        'itemtype',
        'item_id',
    ],
    'sanam_itilsolutions' => [
        'itemtype',
        'item_id',
    ],
    'sanam_knowbaseitemcategories' => [
        'entities_id',
    ],
    'sanam_knowbaseitems_items' => [
        'item',
        'itemtype',
        'item_id',
    ],
    'sanam_networknames' => [
        'name',
    ],
    'sanam_networkports' => [
        'on_device',
    ],
    'sanam_notifications_notificationtemplates' => [
        'notifications_id',
    ],
    'sanam_olalevels_tickets' => [
        'tickets_id',
    ],
    'sanam_problemtemplatehiddenfields' => [
        'problemtemplates_id',
    ],
    'sanam_problemtemplatemandatoryfields' => [
        'problemtemplates_id',
    ],
    'sanam_reservations' => [
        'reservationitems_id',
    ],
    'sanam_slalevels_tickets' => [
        'tickets_id',
    ],
    'sanam_tickettemplatehiddenfields' => [
        'tickettemplates_id',
    ],
    'sanam_tickettemplatemandatoryfields' => [
        'tickettemplates_id',
    ],
];
foreach ($useless_keys as $table => $keys) {
    foreach ($keys as $key) {
        $migration->dropKey($table, $key);
        $migration->migrationOneTable($table);
    }
}

// Add missing keys (based on tools:check_database_keys detection)
$missing_keys = [
    'sanam_apiclients' => [
        'entities_id',
        'is_recursive',
        'name',
    ],
    'sanam_appliances' => [
        'date_mod',
        'is_recursive',
    ],
    'sanam_appliancetypes' => [
        'is_recursive',
    ],
    'sanam_authldapreplicates' => [
        'name',
    ],
    'sanam_authldaps' => [
        'name',
    ],
    'sanam_authmails' => [
        'name',
    ],
    'sanam_blacklistedmailcontents' => [
        'name',
    ],
    'sanam_businesscriticities' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_calendarsegments' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_cartridgeitems' => [
        'is_recursive',
    ],
    'sanam_certificates' => [
        'is_recursive',
    ],
    'sanam_clusters' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_computerantiviruses' => [
        'manufacturers_id',
    ],
    'sanam_computervirtualmachines' => [
        'virtualmachinetypes_id',
    ],
    'sanam_configs' => [
        'name',
    ],
    'sanam_consumableitems' => [
        'is_recursive',
    ],
    'sanam_contacts' => [
        'is_recursive',
    ],
    'sanam_contracts' => [
        'is_recursive',
        'is_template',
    ],
    'sanam_crontasks' => [
        'name',
    ],
    'sanam_dashboards_dashboards' => [
        'name',
    ],
    'sanam_dashboards_rights' => [
        'item' => ['itemtype', 'items_id'],
    ],
    'sanam_datacenters' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_dcrooms' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_devicesensors' => [
        'devicesensormodels_id',
    ],
    'sanam_documents' => [
        'is_recursive',
    ],
    'sanam_documents_items' => [
        'entities_id',
        'is_recursive',
        'date_mod',
    ],
    'sanam_domainrecords' => [
        'is_recursive',
    ],
    'sanam_domainrecordtypes' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_domainrelations' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_domains' => [
        'is_recursive',
    ],
    'sanam_domaintypes' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_enclosures' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_entities' => [
        'authldaps_id',
        'calendars_id',
        'entities_id_software',
        'name',
    ],
    'sanam_fieldblacklists' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_fieldunicities' => [
        'entities_id',
        'is_active',
        'is_recursive',
        'name',
    ],
    'sanam_groups' => [
        'is_recursive',
    ],
    'sanam_groups_users' => [
        'is_dynamic',
    ],
    'sanam_holidays' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_impactcompounds' => [
        'name',
    ],
    'sanam_ipaddresses' => [
        'name',
    ],
    'sanam_ipnetworks' => [
        'ipnetworks_id',
        'is_recursive',
    ],
    'sanam_ipnetworks_vlans' => [
        'vlans_id',
    ],
    'sanam_items_devicebatteries' => [
        'locations_id',
        'states_id',
    ],
    'sanam_items_devicefirmwares' => [
        'locations_id',
        'states_id',
    ],
    'sanam_items_devicegenerics' => [
        'locations_id',
        'states_id',
    ],
    'sanam_items_devicesensors' => [
        'locations_id',
        'states_id',
    ],
    'sanam_items_kanbans' => [
        'users_id',
        'date_creation',
        'date_mod',
    ],
    'sanam_items_operatingsystems' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_items_softwareversions' => [
        'is_deleted',
        'is_deleted_item',
        'is_template_item',
    ],
    'sanam_itilsolutions' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_knowbaseitemcategories' => [
        'knowbaseitemcategories_id',
    ],
    'sanam_knowbaseitems_comments' => [
        'knowbaseitems_id',
        'parent_comment_id',
        'users_id',
        'date_creation',
        'date_mod',
    ],
    'sanam_knowbaseitems_items' => [
        'knowbaseitems_id',
        'date_creation',
        'date_mod',
    ],
    'sanam_knowbaseitems_revisions' => [
        'users_id',
    ],
    'sanam_knowbaseitemtranslations' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_lines' => [
        'is_deleted',
        'groups_id',
        'linetypes_id',
        'locations_id',
        'states_id',
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_links' => [
        'is_recursive',
        'name',
    ],
    'sanam_mailcollectors' => [
        'name',
    ],
    'sanam_manuallinks' => [
        'name',
    ],
    'sanam_monitors' => [
        'date_mod',
    ],
    'sanam_networkaliases' => [
        'fqdns_id',
    ],
    'sanam_networkequipments' => [
        'is_recursive',
    ],
    'sanam_networkports' => [
        'name',
    ],
    'sanam_networkportwifis' => [
        'networkportwifis_id',
    ],
    'sanam_objectlocks' => [
        'users_id',
    ],
    'sanam_olalevels' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_olas' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_operatingsystemeditions' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_operatingsystemkernels' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_operatingsystemkernelversions' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_passivedcequipments' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_pdumodels' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_pdus' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_pdus_plugs' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_pdus_racks' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_planningexternalevents' => [
        'name',
    ],
    'sanam_planningexternaleventtemplates' => [
        'name',
    ],
    'sanam_plugins' => [
        'name',
    ],
    'sanam_printers' => [
        'is_recursive',
    ],
    'sanam_profilerights' => [
        'name',
    ],
    'sanam_profiles' => [
        'name',
    ],
    'sanam_projects' => [
        'is_deleted',
    ],
    'sanam_queuednotifications' => [
        'notificationtemplates_id',
    ],
    'sanam_rackmodels' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_racks' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'sanam_recurrentchanges' => [
        'calendars_id',
        'name',
    ],
    'sanam_refusedequipments' => [
        'name',
    ],
    'sanam_registeredids' => [
        'item' => ['itemtype', 'items_id'],
    ],
    'sanam_remindertranslations' => [
        'date_creation',
        'date_mod',
    ],
    'sanam_reminders' => [
        'name',
    ],
    'sanam_rulerightparameters' => [
        'name',
    ],
    'sanam_rules' => [
        'name',
    ],
    'sanam_savedsearches' => [
        'name',
    ],
    'sanam_softwarecategories' => [
        'name',
    ],
    'sanam_softwarelicenses' => [
        'is_recursive',
        'softwarelicenses_id',
    ],
    'sanam_softwarelicensetypes' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_softwares' => [
        'is_recursive',
    ],
    'sanam_slalevels' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_slas' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_ssovariables' => [
        'name',
    ],
    'sanam_states' => [
        'entities_id',
        'is_recursive',
    ],
    'sanam_suppliers' => [
        'is_recursive',
    ],
    'sanam_ticketrecurrents' => [
        'calendars_id',
        'name',
    ],
    'sanam_tickets_tickets' => [
        'tickets_id_2',
    ],
    'sanam_transfers' => [
        'name',
    ],
    'sanam_users' => [
        'auths_id',
        'default_requesttypes_id',
    ],
    'sanam_virtualmachinestates' => [
        'name',
    ],
    'sanam_virtualmachinesystems' => [
        'name',
    ],
    'sanam_virtualmachinetypes' => [
        'name',
    ],
    'sanam_vlans' => [
        'is_recursive',
    ],
    'sanam_wifinetworks' => [
        'is_recursive',
    ],
];
foreach ($missing_keys as $table => $fields) {
    foreach ($fields as $key => $field) {
        $migration->addKey($table, $field, is_numeric($key) ? '' : $key);
    }
}

// Add missing `date_creation` field on tables that already have `date_mod` field
$tables = [
    'sanam_apiclients',
    'sanam_appliances',
    'sanam_authmails',
    'sanam_transfers',
];
foreach ($tables as $table) {
    $migration->addField($table, 'date_creation', 'timestamp');
    $migration->addKey($table, 'date_creation');
}

// Add missing `date_mod` field on tables that already have `date_creation` field
$tables = [
    'sanam_lockedfields',
];
foreach ($tables as $table) {
    $migration->addField($table, 'date_mod', 'timestamp');
    $migration->addKey($table, 'date_mod');
}

// Rename `date` fields to `date_creation` when value is just a DB insert timestamp
$tables = [
    'sanam_knowbaseitems',
    'sanam_notepads',
    'sanam_projecttasks',
];
foreach ($tables as $table) {
    if ($DB->fieldExists($table, 'date', false)) {
        $migration->dropKey($table, 'date');
        $migration->migrationOneTable($table);
        $migration->changeField($table, 'date', 'date_creation', 'timestamp');
        $migration->addKey($table, 'date_creation');
    }
}
$migration->changeSearchOption(KnowbaseItem::class, 5, 121);
$migration->changeSearchOption(ProjectTask::class, 15, 121);

// Rename `sanam_objectlocks` `date_mod` to `date`
if ($DB->fieldExists('sanam_objectlocks', 'date_mod', false)) {
    $migration->dropKey('sanam_objectlocks', 'date_mod');
    $migration->migrationOneTable('sanam_objectlocks');
    $migration->changeField('sanam_objectlocks', 'date_mod', 'date', 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP');
    $migration->addKey('sanam_objectlocks', 'date');
}

// Rename `date_creation` to `date` when field refers to a valuable date and not just to db insert timestamps
$tables = [
    'sanam_knowbaseitems_revisions',
    'sanam_networkportconnectionlogs',
];
foreach ($tables as $table) {
    if ($DB->fieldExists($table, 'date_creation', false)) {
        $migration->dropKey($table, 'date_creation');
        $migration->migrationOneTable($table);
        $migration->changeField($table, 'date_creation', 'date', 'timestamp');
        $migration->addKey($table, 'date');
    }
}

// Replace -1 default values on entities_id foreign keys (visibility tables)
$tables = [
    'sanam_groups_knowbaseitems',
    'sanam_groups_reminders',
    'sanam_groups_rssfeeds',
    'sanam_knowbaseitems_profiles',
    'sanam_profiles_reminders',
    'sanam_profiles_rssfeeds',
];
foreach ($tables as $table) {
    $migration->addField($table, 'no_entity_restriction', 'boolean', ['update' => 0]);
    $migration->migrationOneTable($table); // Ensure 'no_entity_restriction' is created
    $DB->update(
        $table,
        ['entities_id' => 0, 'no_entity_restriction' => 1],
        ['entities_id' => -1]
    );
    $migration->changeField($table, 'entities_id', 'entities_id', "int {$default_key_sign} DEFAULT NULL");
    $migration->migrationOneTable($table); // Ensure 'entities_id' is nullable
    $DB->update(
        $table,
        ['entities_id' => 'NULL'],
        ['no_entity_restriction' => 1]
    );
}

// Replace -1 default values on sanam_rules.entities_id
$DB->update(
    'sanam_rules',
    ['entities_id' => 0],
    ['entities_id' => -1]
);

// Replace unused -1 default values on entities_id foreign keys
$tables = [
    'sanam_fieldunicities',
    'sanam_savedsearches',
];
foreach ($tables as $table) {
    $migration->changeField($table, 'entities_id', 'entities_id', "int {$default_key_sign} NOT NULL DEFAULT 0");
}

// Replace -1 default values on sanam_queuednotifications.items_id
$DB->update(
    'sanam_queuednotifications',
    ['items_id' => 0],
    ['items_id' => -1]
);
