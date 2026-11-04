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

use Sanam\Socket;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */
$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('sanam_cabletypes')) {
    $query = "CREATE TABLE `sanam_cabletypes` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

if (!$DB->tableExists('sanam_cablestrands')) {
    $query = "CREATE TABLE `sanam_cablestrands` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

if (!$DB->tableExists('sanam_socketmodels')) {
    $query = "CREATE TABLE `sanam_socketmodels` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET= {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

if (!$DB->tableExists('sanam_cables')) {
    $query = "CREATE TABLE `sanam_cables` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `is_recursive` tinyint NOT NULL DEFAULT '0',
      `itemtype_endpoint_a` varchar(255) DEFAULT NULL,
      `itemtype_endpoint_b` varchar(255) DEFAULT NULL,
      `items_id_endpoint_a` int {$default_key_sign} NOT NULL DEFAULT '0',
      `items_id_endpoint_b` int {$default_key_sign} NOT NULL DEFAULT '0',
      `socketmodels_id_endpoint_a` int {$default_key_sign} NOT NULL DEFAULT '0',
      `socketmodels_id_endpoint_b` int {$default_key_sign} NOT NULL DEFAULT '0',
      `sockets_id_endpoint_a` int {$default_key_sign} NOT NULL DEFAULT '0',
      `sockets_id_endpoint_b` int {$default_key_sign} NOT NULL DEFAULT '0',
      `cablestrands_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `color` varchar(255) DEFAULT NULL,
      `otherserial` varchar(255) DEFAULT NULL,
      `states_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `users_id_tech` int {$default_key_sign} NOT NULL DEFAULT '0',
      `cabletypes_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `item_endpoint_a` (`itemtype_endpoint_a`,`items_id_endpoint_a`),
      KEY `item_endpoint_b` (`itemtype_endpoint_b`,`items_id_endpoint_b`),
      KEY `items_id_endpoint_b` (`items_id_endpoint_b`),
      KEY `items_id_endpoint_a` (`items_id_endpoint_a`),
      KEY `socketmodels_id_endpoint_a` (`socketmodels_id_endpoint_a`),
      KEY `socketmodels_id_endpoint_b` (`socketmodels_id_endpoint_b`),
      KEY `sockets_id_endpoint_a` (`sockets_id_endpoint_a`),
      KEY `sockets_id_endpoint_b` (`sockets_id_endpoint_b`),
      KEY `cablestrands_id` (`cablestrands_id`),
      KEY `states_id` (`states_id`),
      KEY `complete` (`entities_id`,`name`),
      KEY `is_recursive` (`is_recursive`),
      KEY `users_id_tech` (`users_id_tech`),
      KEY `cabletypes_id` (`cabletypes_id`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}
$migration->addField('sanam_states', 'is_visible_cable', 'bool', [
    'value' => 1,
    'after' => 'is_visible_appliance',
]);
$migration->addKey('sanam_states', 'is_visible_cable');

if (!$DB->tableExists('sanam_sockets')) {
    //create socket table
    $query = "CREATE TABLE `sanam_sockets` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `position` int NOT NULL DEFAULT '0',
      `locations_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `name` varchar(255) DEFAULT NULL,
      `socketmodels_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `wiring_side` tinyint DEFAULT '1',
      `itemtype` varchar(255) DEFAULT NULL,
      `items_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `networkports_id` int {$default_key_sign} NOT NULL DEFAULT '0',
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `socketmodels_id` (`socketmodels_id`),
      KEY `location_name` (`locations_id`,`name`),
      KEY `item` (`itemtype`,`items_id`),
      KEY `networkports_id` (`networkports_id`),
      KEY `wiring_side` (`wiring_side`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

if ($DB->tableExists('sanam_netpoints')) {
    //migrate link between NetworkPort and Socket
    // BEFORE : supported by NetworkPortEthernet / NetworkPortFiberchannel with 'netpoints_id' foreign key
    // AFTER  : supported by Socket with (itemtype, items_id, networkports_id)
    $tables_to_migrate = ['sanam_networkportethernets', 'sanam_networkportfiberchannels'];
    foreach ($tables_to_migrate as $table) {
        if (!$DB->fieldExists($table, 'netpoints_id')) {
            continue;
        }
        $criteria = [
            'SELECT' => [
                "sanam_networkports.id AS networkports_id",
                "sanam_networkports.logical_number",
                "sanam_networkports.itemtype",
                "sanam_networkports.items_id",
                "sanam_netpoints.locations_id",
                "sanam_netpoints.name",
                "sanam_netpoints.entities_id",
                "sanam_netpoints.date_creation",
                "sanam_netpoints.date_mod",
            ],
            'FROM'      => $table,
            'INNER JOIN' => [
                'sanam_networkports' => [
                    'FKEY' => [
                        'sanam_networkports'     => 'id',
                        $table   => 'networkports_id',
                    ],
                ],
                'sanam_netpoints' => [
                    'FKEY' => [
                        'sanam_netpoints'        => 'id',
                        $table   => 'netpoints_id',
                    ],
                ],
            ],
        ];

        $iterator = $DB->request($criteria);

        foreach ($iterator as $data) {
            $input = [
                'name'            => $data['name'],
                'locations_id'    => $data['locations_id'],
                'position'        => $data['logical_number'],
                'itemtype'        => $data['itemtype'],
                'items_id'        => $data['items_id'],
                'networkports_id' => $data['networkports_id'],
                'date_creation'   => $data['date_creation'],
                'date_mod'        => $data['date_mod'],
            ];
            $DB->insert('sanam_sockets', $input);
        }
    }
    //remove "useless "netpoints_id" field
    $migration->dropField('sanam_networkportethernets', 'netpoints_id');
    $migration->dropField('sanam_networkportfiberchannels', 'netpoints_id');
}

//drop table sanam_netpoints
$migration->dropTable('sanam_netpoints');

if (!$DB->tableExists('sanam_networkportfiberchanneltypes')) {
    $query = "CREATE TABLE `sanam_networkportfiberchanneltypes` (
      `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
      `name` varchar(255) DEFAULT NULL,
      `comment` text,
      `date_mod` timestamp NULL DEFAULT NULL,
      `date_creation` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `name` (`name`),
      KEY `date_mod` (`date_mod`),
      KEY `date_creation` (`date_creation`)
      ) ENGINE = InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

$migration->addField('sanam_networkportfiberchannels', 'networkportfiberchanneltypes_id', "int {$default_key_sign} NOT NULL DEFAULT '0'", ['after' => 'items_devicenetworkcards_id']);
$migration->addKey('sanam_networkportfiberchannels', 'networkportfiberchanneltypes_id', 'type');

$DELFROMDISPLAYPREF['Socket'] = [5, 6, 9, 8, 7]; // Remove display prefs generated in SANAM 10.0.0-beta1
$ADDTODISPLAYPREF[Socket::class] = [5, 6, 9, 8, 7];
$ADDTODISPLAYPREF['Cable'] = [4, 31, 6, 15, 24, 8, 10, 13, 14];

//rename profilerights values ('netpoint' to 'cable_management')
$migration->addPostQuery(
    $DB->buildUpdate(
        'sanam_profilerights',
        ['name' => 'cable_management'],
        ['name' => 'netpoint']
    )
);
