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
 * @var array $ADDTODISPLAYPREF
 */
$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('sanam_databaseinstancetypes')) {
    $query = "CREATE TABLE `sanam_databaseinstancetypes` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

if (!$DB->tableExists('sanam_databaseinstancecategories')) {
    $query = "CREATE TABLE `sanam_databaseinstancecategories` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `name` varchar(255) DEFAULT NULL,
         `comment` text,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_creation` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

if (!$DB->tableExists('sanam_databaseinstances')) {
    $query = "CREATE TABLE `sanam_databaseinstances` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) NOT NULL DEFAULT '',
         `version` varchar(255) NOT NULL DEFAULT '',
         `port` varchar(10) NOT NULL DEFAULT '',
         `path` varchar(255) NOT NULL DEFAULT '',
         `size` int NOT NULL DEFAULT '0',
         `databaseinstancetypes_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `databaseinstancecategories_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `manufacturers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `users_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
         `groups_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
         `states_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `itemtype` varchar(100) NOT NULL DEFAULT '',
         `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_onbackup` tinyint NOT NULL DEFAULT '0',
         `is_active` tinyint NOT NULL DEFAULT '0',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `is_helpdesk_visible` tinyint NOT NULL DEFAULT '1',
         `is_dynamic` tinyint NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_lastboot` timestamp NULL DEFAULT NULL,
         `date_lastbackup` timestamp NULL DEFAULT NULL,
         `comment` text,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `name` (`name`),
         KEY `databaseinstancetypes_id` (`databaseinstancetypes_id`),
         KEY `databaseinstancecategories_id` (`databaseinstancecategories_id`),
         KEY `locations_id` (`locations_id`),
         KEY `manufacturers_id` (`manufacturers_id`),
         KEY `users_id_tech` (`users_id_tech`),
         KEY `groups_id_tech` (`groups_id_tech`),
         KEY `states_id` (`states_id`),
         KEY `item` (`itemtype`,`items_id`),
         KEY `is_active` (`is_active`),
         KEY `is_deleted` (`is_deleted`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`),
         KEY `is_helpdesk_visible` (`is_helpdesk_visible`),
         KEY `is_dynamic` (`is_dynamic`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

// Create sanam_databaseinstances itemtype/items_id if they are not existing (datamodel changed during v10.0 development)
if (!$DB->fieldExists('sanam_databaseinstances', 'itemtype') || !$DB->fieldExists('sanam_databaseinstances', 'items_id')) {
    //1- migrate sanam_databaseinstances table
    $migration->addField('sanam_databaseinstances', 'itemtype', 'string', [
        'after' => 'states_id',
    ]);
    $migration->addField('sanam_databaseinstances', 'items_id', "int {$default_key_sign} NOT NULL DEFAULT '0'", [
        'after' => 'itemtype',
    ]);
    $migration->addKey('sanam_databaseinstances', ['itemtype', 'items_id'], 'item');
    $migration->migrationOneTable('sanam_databaseinstances');
}
// Delete old table
if ($DB->tableExists('sanam_databaseinstances_items')) {
    $migration->dropTable('sanam_databaseinstances_items');
}

if (!$DB->tableExists('sanam_databases')) {
    $query = "CREATE TABLE `sanam_databases` (
         `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
         `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_recursive` tinyint NOT NULL DEFAULT '0',
         `name` varchar(255) NOT NULL DEFAULT '',
         `size` int NOT NULL DEFAULT '0',
         `databaseinstances_id` int {$default_key_sign} NOT NULL DEFAULT '0',
         `is_onbackup` tinyint NOT NULL DEFAULT '0',
         `is_active` tinyint NOT NULL DEFAULT '0',
         `is_deleted` tinyint NOT NULL DEFAULT '0',
         `is_dynamic` tinyint NOT NULL DEFAULT '0',
         `date_creation` timestamp NULL DEFAULT NULL,
         `date_mod` timestamp NULL DEFAULT NULL,
         `date_update` timestamp NULL DEFAULT NULL,
         `date_lastbackup` timestamp NULL DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `is_recursive` (`is_recursive`),
         KEY `name` (`name`),
         KEY `is_active` (`is_active`),
         KEY `is_deleted` (`is_deleted`),
         KEY `is_dynamic` (`is_dynamic`),
         KEY `date_creation` (`date_creation`),
         KEY `date_mod` (`date_mod`),
         KEY `databaseinstances_id` (`databaseinstances_id`)
      ) ENGINE = InnoDB ROW_FORMAT = DYNAMIC DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation};";
    $DB->doQuery($query);
}

if ($DB->fieldExists('sanam_states', 'is_visible_database')) {
    // Dev migration
    $migration->changeField('sanam_states', 'is_visible_database', 'is_visible_databaseinstance', 'bool', ['value' => 1]);
    $migration->dropKey('sanam_states', 'is_visible_database');
} elseif (!$DB->fieldExists('sanam_states', 'is_visible_databaseinstance')) {
    $migration->addField('sanam_states', 'is_visible_databaseinstance', 'bool', [
        'value' => 1,
        'after' => 'is_visible_appliance',
    ]);
}
$migration->addKey('sanam_states', 'is_visible_databaseinstance');

// Create sanam_databases is_dynamic if not exist (datamodel changed during v10.0 development)
if (!$DB->fieldExists('sanam_databases', 'is_dynamic')) {
    $migration->addField('sanam_databases', 'is_dynamic', "tinyint NOT NULL DEFAULT '0'", [
        'after' => 'is_deleted',
    ]);
    $migration->addKey('sanam_databases', 'is_dynamic');
    $migration->migrationOneTable('sanam_databases');
}

$migration->addRight('database', ALLSTANDARDRIGHT);
$ADDTODISPLAYPREF['Database'] = [2, 3, 6, 9, 10];
