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

use Sanam\DBAL\QueryExpression;

/**
 * Update from 9.1 to 9.2
 *
 * @return bool
 **/
function update91xto920()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $current_config   = Config::getConfigurationValues('core');
    $updateresult     = true;
    $ADDTODISPLAYPREF = [];

    $migration->setVersion('9.2');

    // add business criticity
    $migration->addField("sanam_infocoms", "businesscriticities_id", "integer");
    $migration->migrationOneTable('sanam_infocoms');
    $migration->addKey("sanam_infocoms", "businesscriticities_id");

    if (!$DB->tableExists("sanam_businesscriticities")) {
        $query = "CREATE TABLE `sanam_businesscriticities` (
        `id` int NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        `entities_id` int NOT NULL DEFAULT '0',
        `is_recursive` tinyint NOT NULL DEFAULT '0',
        `comment` text COLLATE utf8_unicode_ci,
        `date_mod` datetime DEFAULT NULL,
        `date_creation` datetime DEFAULT NULL,
        `businesscriticities_id` int NOT NULL DEFAULT '0',
        `completename` text COLLATE utf8_unicode_ci,
        `level` int NOT NULL DEFAULT '0',
        `ancestors_cache` longtext COLLATE utf8_unicode_ci,
        `sons_cache` longtext COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `unicity` (`businesscriticities_id`,`name`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    // Issue #1250 - Add decimal to monitor size
    $migration->changeField('sanam_monitors', 'size', 'size', 'DECIMAL(5,2) NOT NULL DEFAULT "0"');

    //Make software license type a tree dropdown
    $migration->addField("sanam_softwarelicensetypes", "softwarelicensetypes_id", "integer");
    $migration->addField("sanam_softwarelicensetypes", "level", "integer");
    $migration->addField("sanam_softwarelicensetypes", "ancestors_cache", "longtext");
    $migration->addField("sanam_softwarelicensetypes", "sons_cache", "longtext");
    $migration->addField("sanam_softwarelicensetypes", "entities_id", "integer");
    $migration->addField("sanam_softwarelicensetypes", "is_recursive", "bool");
    $tree = $migration->addField("sanam_softwarelicensetypes", "completename", "text");
    $migration->migrationOneTable('sanam_softwarelicensetypes');
    $migration->addKey("sanam_softwarelicensetypes", "softwarelicensetypes_id");

    //First time the dropdown is changed from CommonDropdown to CommonTreeDropdown
    if ($tree) {
        $DB->update(
            "sanam_softwarelicensetypes",
            [
                'completename' =>  new QueryExpression(DBmysql::quoteName("name")),
                'is_recursive' => "1",
            ],
            [true]
        );
    }

    // give READ right on components to profiles having UPDATE right
    $DB->update(
        "sanam_profilerights",
        [
            'rights' => new QueryExpression($DB->quoteName("rights") . " | " . READ),
        ],
        [
            new QueryExpression(DBmysql::quoteName("rights") . " & " . DBmysql::quoteValue(UPDATE)),
            'name' => "device",
        ]
    );

    $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item link to tickets'));
    if (!$DB->tableExists('sanam_knowbaseitems_items')) {
        $query = "CREATE TABLE `sanam_knowbaseitems_items` (
                 `id` int NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int NOT NULL,
                 `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                 `items_id` int NOT NULL DEFAULT '0',
                 `date_creation` datetime DEFAULT NULL,
                 `date_mod` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 UNIQUE KEY `unicity` (`itemtype`,`items_id`,`knowbaseitems_id`),
                 KEY `itemtype` (`itemtype`),
                 KEY `item_id` (`items_id`),
                 KEY `item` (`itemtype`,`items_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item revisions'));
    if (!$DB->tableExists('sanam_knowbaseitems_revisions')) {
        $query = "CREATE TABLE `sanam_knowbaseitems_revisions` (
                 `id` int NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int NOT NULL,
                 `revision` int NOT NULL,
                 `name` text COLLATE utf8_unicode_ci,
                 `answer` longtext COLLATE utf8_unicode_ci,
                 `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `users_id` int NOT NULL DEFAULT '0',
                 `date_creation` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`),
                 UNIQUE KEY `unicity` (`knowbaseitems_id`, `revision`, `language`),
                 KEY `revision` (`revision`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    $migration->addField("sanam_knowbaseitemtranslations", "users_id", "integer");
    $migration->migrationOneTable("sanam_knowbaseitemtranslations");
    $migration->addKey("sanam_knowbaseitemtranslations", "users_id");

    //set kb translations users...
    $knowitems_iterator = $DB->request([
        'SELECT' => ['sanam_knowbaseitems.id', 'sanam_knowbaseitems.users_id'],
        'FROM' => 'sanam_knowbaseitems',
        'INNER JOIN' => [
            "sanam_knowbaseitemtranslations" => [
                'FKEY' => [
                    'sanam_knowbaseitemtranslations' => 'knowbaseitems_id',
                    'sanam_knowbaseitems'            => 'id',
                ],
            ],
        ],
    ]);
    foreach ($knowitems_iterator as $knowitems) {
        $DB->update(
            "sanam_knowbaseitemtranslations",
            ['users_id' => $knowitems['users_id']],
            ['knowbaseitems_id' => $knowitems['id']]
        );
    }

    $migration->addField("sanam_knowbaseitemtranslations", "date_mod", "DATETIME");
    $migration->addField("sanam_knowbaseitemtranslations", "date_creation", "DATETIME");

    $migration->displayMessage(sprintf(__('Add of - %s to database'), 'Knowbase item comments'));
    if (!$DB->tableExists('sanam_knowbaseitems_comments')) {
        $query = "CREATE TABLE `sanam_knowbaseitems_comments` (
                 `id` int NOT NULL AUTO_INCREMENT,
                 `knowbaseitems_id` int NOT NULL,
                 `users_id` int NOT NULL DEFAULT '0',
                 `language` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
                 `comment` text COLLATE utf8_unicode_ci NOT NULL,
                 `parent_comment_id` int DEFAULT NULL,
                 `date_creation` datetime DEFAULT NULL,
                 `date_mod` datetime DEFAULT NULL,
                 PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    $DB->update(
        "sanam_profilerights",
        [
            'rights' => new QueryExpression(
                DBmysql::quoteName("rights") . " | " . DBmysql::quoteValue(KnowbaseItem::COMMENTS)
            ),
        ],
        ['name' => "knowbase"]
    );

    // add kb category to task categories
    $migration->addField("sanam_taskcategories", "knowbaseitemcategories_id", "integer");
    $migration->migrationOneTable("sanam_taskcategories");
    $migration->addKey("sanam_taskcategories", "knowbaseitemcategories_id");

    // #1476 - Add users_id on sanam_documents_items
    $migration->addField("sanam_documents_items", "users_id", "integer", ['null' => true]);
    $migration->migrationOneTable("sanam_documents_items");
    $migration->addKey("sanam_documents_items", "users_id");
    $migration->addPostQuery(
        $DB->buildUpdate(
            'sanam_documents_items',
            [
                'sanam_documents_items.users_id' => new QueryExpression(DBmysql::quoteName('sanam_documents.users_id')),
            ],
            [
                'sanam_documents_items.documents_id' => new QueryExpression(DBmysql::quoteName('sanam_documents.id')),
            ],
            [
                'LEFT JOIN' => [
                    'sanam_documents' => [
                        'ON' => [
                            'sanam_documents_items' => 'documents_id',
                            'sanam_documents' => 'id',
                        ],
                    ],
                ],
            ]
        ),
        "9.2 update set users_id on sanam_documents_items"
    );

    //add product number
    $product_types = ['Computer',
        'Printer',
        'NetworkEquipment',
        'Phone',
        'Peripheral',
        'Monitor',
    ];

    foreach ($product_types as $type) {
        if (class_exists($type . 'Model')) {
            $table = getTableForItemType($type . 'Model');
            $migration->addField($table, 'product_number', 'string', ['after' => 'comment']);
            $migration->migrationOneTable($table);
            $migration->addKey($table, 'product_number');
        }
    }

    // add fields on every item_device tables
    $tables = ['sanam_items_devicecases',
        'sanam_items_devicecontrols',
        'sanam_items_devicedrives',
        'sanam_items_devicegraphiccards',
        'sanam_items_deviceharddrives',
        'sanam_items_devicememories',
        'sanam_items_devicemotherboards',
        'sanam_items_devicenetworkcards',
        'sanam_items_devicepcis',
        'sanam_items_devicepowersupplies',
        'sanam_items_deviceprocessors',
        'sanam_items_devicesoundcards',
    ];

    //add serial, location and state on each devices items
    foreach ($tables as $table) {
        $migration->addField($table, "otherserial", "varchar(255) NULL DEFAULT NULL");
        $migration->addField($table, "locations_id", "int NOT NULL DEFAULT '0'");
        $migration->addField($table, "states_id", "int NOT NULL DEFAULT '0'");
        $migration->migrationOneTable($table);
        $migration->addKey($table, 'otherserial');
        $migration->addKey($table, 'locations_id');
        $migration->addKey($table, 'states_id');
    }

    // Create tables :
    $tables = ['sanam_devicecasemodels',
        'sanam_devicecontrolmodels',
        'sanam_devicedrivemodels',
        'sanam_devicegraphiccardmodels',
        'sanam_deviceharddrivemodels',
        'sanam_devicememorymodels',
        'sanam_devicemotherboardmodels',
        'sanam_devicenetworkcardmodels',
        'sanam_devicepcimodels',
        'sanam_devicepowersupplymodels',
        'sanam_deviceprocessormodels',
        'sanam_devicesoundcardmodels',
        'sanam_devicegenericmodels',
        'sanam_devicebatterymodels',
        'sanam_devicefirmwaremodels',
        'sanam_devicesensormodels',
    ];

    foreach ($tables as $table) {
        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                      `id` INT NOT NULL AUTO_INCREMENT,
                      `name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                      `comment` TEXT NULL COLLATE 'utf8_unicode_ci',
                      `product_number` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                      PRIMARY KEY (`id`),
                      INDEX `name` (`name`),
                      INDEX `product_number` (`product_number`)
                   ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
            $DB->doQuery($query);
        }
    }

    // Add a field in sanam_device* tables :
    $tables = ['sanam_devicecases'         => 'devicecasemodels_id',
        'sanam_devicecontrols'      => 'devicecontrolmodels_id',
        'sanam_devicedrives'        => 'devicedrivemodels_id',
        'sanam_devicegraphiccards'  => 'devicegraphiccardmodels_id',
        'sanam_deviceharddrives'    => 'deviceharddrivemodels_id',
        'sanam_devicememories'      => 'devicememorymodels_id',
        'sanam_devicemotherboards'  => 'devicemotherboardmodels_id',
        'sanam_devicenetworkcards'  => 'devicenetworkcardmodels_id',
        'sanam_devicepcis'          => 'devicepcimodels_id',
        'sanam_devicepowersupplies' => 'devicepowersupplymodels_id',
        'sanam_deviceprocessors'    => 'deviceprocessormodels_id',
        'sanam_devicesoundcards'    => 'devicesoundcardmodels_id',
    ];

    foreach ($tables as $table => $field) {
        $migration->addField($table, $field, 'int DEFAULT NULL', ['after' => 'is_recursive']);
        $migration->migrationOneTable($table);
        $migration->addKey($table, $field);
    }

    if (!$DB->tableExists('sanam_devicegenerics')) {
        $query = "CREATE TABLE `sanam_devicegenerics` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `devicegenerictypes_id` int NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int NOT NULL DEFAULT '0',
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `states_id` int NOT NULL DEFAULT '0',
                  `devicegenericmodels_id` int DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `devicegenerictypes_id` (`devicegenerictypes_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `states_id` (`states_id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `devicegenericmodels_id` (`devicegenericmodels_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_items_devicegenerics')) {
        $query = "CREATE TABLE `sanam_items_devicegenerics` (
                   `id` INT NOT NULL AUTO_INCREMENT,
                   `items_id` INT NOT NULL DEFAULT '0',
                   `itemtype` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `devicegenerics_id` INT NOT NULL DEFAULT '0',
                   `is_deleted` TINYINT NOT NULL DEFAULT '0',
                   `is_dynamic` TINYINT NOT NULL DEFAULT '0',
                   `entities_id` INT NOT NULL DEFAULT '0',
                   `is_recursive` TINYINT NOT NULL DEFAULT '0',
                   `serial` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `otherserial` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `locations_id` INT NOT NULL DEFAULT '0',
                   `states_id` INT NOT NULL DEFAULT '0',
                   PRIMARY KEY (`id`),
                   INDEX `computers_id` (`items_id`),
                   INDEX `devicegenerics_id` (`devicegenerics_id`),
                   INDEX `is_deleted` (`is_deleted`),
                   INDEX `is_dynamic` (`is_dynamic`),
                   INDEX `entities_id` (`entities_id`),
                   INDEX `is_recursive` (`is_recursive`),
                   INDEX `serial` (`serial`),
                   INDEX `item` (`itemtype`, `items_id`),
                   INDEX `otherserial` (`otherserial`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_devicegenerictypes')) {
        $query = "CREATE TABLE `sanam_devicegenerictypes` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                  `comment` TEXT NULL COLLATE 'utf8_unicode_ci',
                   PRIMARY KEY (`id`),
                   INDEX `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_devicebatteries')) {
        $query = "CREATE TABLE `sanam_devicebatteries` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int NOT NULL DEFAULT '0',
                  `voltage` int DEFAULT NULL,
                  `capacity` int DEFAULT NULL,
                  `devicebatterytypes_id` int NOT NULL DEFAULT '0',
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `devicebatterymodels_id` int DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `devicebatterymodels_id` (`devicebatterymodels_id`),
                  KEY `devicebatterytypes_id` (`devicebatterytypes_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_items_devicebatteries')) {
        $query = "CREATE TABLE `sanam_items_devicebatteries` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `items_id` int NOT NULL DEFAULT '0',
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `devicebatteries_id` int NOT NULL DEFAULT '0',
                  `manufacturing_date` date DEFAULT NULL,
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `is_dynamic` tinyint NOT NULL DEFAULT '0',
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `locations_id` int NOT NULL DEFAULT '0',
                  `states_id` int NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `computers_id` (`items_id`),
                  KEY `devicebatteries_id` (`devicebatteries_id`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `is_dynamic` (`is_dynamic`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `serial` (`serial`),
                  KEY `item` (`itemtype`,`items_id`),
                  KEY `otherserial` (`otherserial`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_devicebatterytypes')) {
        $query = "CREATE TABLE `sanam_devicebatterytypes` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_devicefirmwares')) {
        $query = "CREATE TABLE `sanam_devicefirmwares` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int NOT NULL DEFAULT '0',
                  `date` date DEFAULT NULL,
                  `version` varchar(255) DEFAULT NULL,
                  `devicefirmwaretypes_id` int NOT NULL DEFAULT '0',
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `devicefirmwaremodels_id` int DEFAULT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `devicefirmwaremodels_id` (`devicefirmwaremodels_id`),
                  KEY `devicefirmwaretypes_id` (`devicefirmwaretypes_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }
    if (!$DB->tableExists('sanam_items_devicefirmwares')) {
        $query = "CREATE TABLE `sanam_items_devicefirmwares` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `items_id` int NOT NULL DEFAULT '0',
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `devicefirmwares_id` int NOT NULL DEFAULT '0',
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `is_dynamic` tinyint NOT NULL DEFAULT '0',
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `locations_id` int NOT NULL DEFAULT '0',
                  `states_id` int NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `computers_id` (`items_id`),
                  KEY `devicefirmwares_id` (`devicefirmwares_id`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `is_dynamic` (`is_dynamic`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `serial` (`serial`),
                  KEY `item` (`itemtype`,`items_id`),
                  KEY `otherserial` (`otherserial`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }
    if (!$DB->tableExists('sanam_devicefirmwaretypes')) {
        $query = "CREATE TABLE `sanam_devicefirmwaretypes` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);

        $DB->insert("sanam_devicefirmwaretypes", [
            'id'              => "1",
            'name'            => "BIOS",
            'comment'         => null,
            'date_mod'        => null,
            'date_creation'   => null,
        ]);

        $DB->insert("sanam_devicefirmwaretypes", [
            'id'              => "2",
            'name'            => "UEFI",
            'comment'         => null,
            'date_mod'        => null,
            'date_creation'   => null,
        ]);

        $DB->insert("sanam_devicefirmwaretypes", [
            'id'              => "3",
            'name'            => "Firmware",
            'comment'         => null,
            'date_mod'        => null,
            'date_creation'   => null,
        ]);
    }

    //Device sensors
    if (!$DB->tableExists('sanam_devicesensors')) {
        $query = "CREATE TABLE `sanam_devicesensors` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `designation` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `devicesensortypes_id` int NOT NULL DEFAULT '0',
                  `devicesensormodels_id` int NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `manufacturers_id` int NOT NULL DEFAULT '0',
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `states_id` int NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `designation` (`designation`),
                  KEY `manufacturers_id` (`manufacturers_id`),
                  KEY `devicesensortypes_id` (`devicesensortypes_id`),
                  KEY `entities_id` (`entities_id`),
                  KEY `is_recursive` (`is_recursive`),
                  KEY `locations_id` (`locations_id`),
                  KEY `states_id` (`states_id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_items_devicesensors')) {
        $query = "CREATE TABLE `sanam_items_devicesensors` (
                   `id` INT NOT NULL AUTO_INCREMENT,
                   `items_id` INT NOT NULL DEFAULT '0',
                   `itemtype` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `devicesensors_id` INT NOT NULL DEFAULT '0',
                   `is_deleted` TINYINT NOT NULL DEFAULT '0',
                   `is_dynamic` TINYINT NOT NULL DEFAULT '0',
                   `entities_id` INT NOT NULL DEFAULT '0',
                   `is_recursive` TINYINT NOT NULL DEFAULT '0',
                   `serial` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `otherserial` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                   `locations_id` INT NOT NULL DEFAULT '0',
                   `states_id` INT NOT NULL DEFAULT '0',
                   PRIMARY KEY (`id`),
                   INDEX `computers_id` (`items_id`),
                   INDEX `devicesensors_id` (`devicesensors_id`),
                   INDEX `is_deleted` (`is_deleted`),
                   INDEX `is_dynamic` (`is_dynamic`),
                   INDEX `entities_id` (`entities_id`),
                   INDEX `is_recursive` (`is_recursive`),
                   INDEX `serial` (`serial`),
                   INDEX `item` (`itemtype`, `items_id`),
                   INDEX `otherserial` (`otherserial`)
                )
                COLLATE='utf8_unicode_ci'
                ENGINE=InnoDB;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_devicesensortypes')) {
        $query = "CREATE TABLE `sanam_devicesensortypes` (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
                  `comment` TEXT NULL COLLATE 'utf8_unicode_ci',
                   PRIMARY KEY (`id`),
                   INDEX `name` (`name`)
                )
                COLLATE='utf8_unicode_ci' ENGINE=InnoDB;";
        $DB->doQuery($query);
    }

    //Father/son for Software licenses
    $migration->addField("sanam_softwarelicenses", "softwarelicenses_id", "integer", ['after' => 'softwares_id']);
    $new = $migration->addField("sanam_softwarelicenses", "completename", "text", ['after' => 'softwarelicenses_id']);
    $migration->addField("sanam_softwarelicenses", "level", "integer", ['after' => 'completename']);
    $migration->migrationOneTable("sanam_softwarelicenses");
    if ($new) {
        $DB->update(
            "sanam_softwarelicenses",
            [
                'completename' => new QueryExpression(DBmysql::quoteName("name")),
            ],
            [true]
        );
    }

    // add template key to itiltasks
    $migration->addField("sanam_tickettasks", "tasktemplates_id", "integer");
    $migration->migrationOneTable('sanam_tickettasks');
    $migration->addKey("sanam_tickettasks", "tasktemplates_id");

    $migration->addField("sanam_problemtasks", "tasktemplates_id", "integer");
    $migration->migrationOneTable('sanam_problemtasks');
    $migration->addKey("sanam_problemtasks", "tasktemplates_id");

    $migration->addField("sanam_changetasks", "tasktemplates_id", "integer");
    $migration->migrationOneTable('sanam_changetasks');
    $migration->addKey("sanam_changetasks", "tasktemplates_id");

    // add missing fields to tasktemplate
    $migration->addField("sanam_tasktemplates", "state", "integer");
    $migration->addField("sanam_tasktemplates", "is_private", "bool");
    $migration->addField("sanam_tasktemplates", "users_id_tech", "integer");
    $migration->addField("sanam_tasktemplates", "groups_id_tech", "integer");
    $migration->migrationOneTable('sanam_tasktemplates');
    $migration->addKey("sanam_tickettasks", "is_private");
    $migration->addKey("sanam_tickettasks", "users_id_tech");
    $migration->addKey("sanam_tickettasks", "groups_id_tech");

    // #1735 - Add new notifications
    $new_notifications  = [
        'requester_user'  => ['label'      => 'New user in requesters',
            'targets_id' => Notification::AUTHOR,
        ],
        'requester_group' => ['label'      => 'New group in requesters',
            'targets_id' => Notification::REQUESTER_GROUP,
        ],
        'observer_user'   => ['label'      => 'New user in observers',
            'targets_id' => Notification::OBSERVER,
        ],
        'observer_group'  => ['label'      => 'New group in observers',
            'targets_id' => Notification::OBSERVER_GROUP,
        ],
        'assign_user'     => ['label'      => 'New user in assignees',
            'targets_id' => Notification::ASSIGN_TECH,
        ],
        'assign_group'    => ['label'      => 'New group in assignees',
            'targets_id' => Notification::ITEM_TECH_GROUP_IN_CHARGE,
        ],
        'assign_supplier' => ['label'      => 'New supplier in assignees',
            'targets_id' => Notification::SUPPLIER,
        ],
    ];

    if ($DB->fieldExists("sanam_notifications", "mode", false)) {
        $notificationtemplates_id = $DB->request([
            'FROM' => 'sanam_notificationtemplates',
            'WHERE' => [
                'name'     => 'Tickets',
                'itemtype' => 'Ticket',
            ],
        ])->current()['id'] ?? 0;

        if ($notificationtemplates_id > 0) {
            foreach ($new_notifications as $event => $notif_options) {
                $DB->insert(
                    'sanam_notifications',
                    [
                        'name'                     => $notif_options['label'],
                        'itemtype'                 => 'Ticket',
                        'event'                    => $event,
                        'mode'                     => 'mailing',
                        'notificationtemplates_id' => $notificationtemplates_id,
                        'comment'                  => null,
                        'entities_id'              => 0,
                        'is_recursive'             => 1,
                        'is_active'                => 1,
                        'date_creation'            => new QueryExpression('NOW()'),
                        'date_mod'                 => new QueryExpression('NOW()'),
                    ]
                );
                $notifications_id = $DB->insertId();

                $DB->insert(
                    'sanam_notificationtargets',
                    [
                        'items_id'         => $notif_options['targets_id'],
                        'type'             => 1,
                        'notifications_id' => $notifications_id,
                    ]
                );
            }
        }
    }

    $migration->addField('sanam_states', 'is_visible_certificate', 'bool', ['value' => 1]);
    $migration->addKey('sanam_states', 'is_visible_certificate');

    /** ************ New SLM structure ************ */
    if (!$DB->tableExists('sanam_olas')) {
        $query = "CREATE TABLE `sanam_olas` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `entities_id` int NOT NULL DEFAULT '0',
                  `is_recursive` tinyint NOT NULL DEFAULT '0',
                  `type` int NOT NULL DEFAULT '0',
                  `comment` text COLLATE utf8_unicode_ci,
                  `number_time` int NOT NULL,
                  `calendars_id` int NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `definition_time` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `end_of_working_day` tinyint NOT NULL DEFAULT '0',
                  `date_creation` datetime DEFAULT NULL,
                  `slms_id` int NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `calendars_id` (`calendars_id`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  KEY `slms_id` (`slms_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_olalevelactions')) {
        $query = "CREATE TABLE `sanam_olalevelactions` (
               `id` int NOT NULL AUTO_INCREMENT,
               `olalevels_id` int NOT NULL DEFAULT '0',
               `action_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `field` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               PRIMARY KEY (`id`),
               KEY `olalevels_id` (`olalevels_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_olalevelcriterias')) {
        $query = "CREATE TABLE `sanam_olalevelcriterias` (
               `id` int NOT NULL AUTO_INCREMENT,
               `olalevels_id` int NOT NULL DEFAULT '0',
               `criteria` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `condition` int NOT NULL DEFAULT '0' COMMENT 'see define.php PATTERN_* and REGEX_* constant',
               `pattern` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               PRIMARY KEY (`id`),
               KEY `olalevels_id` (`olalevels_id`),
               KEY `condition` (`condition`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_olalevels')) {
        $query = "CREATE TABLE `sanam_olalevels` (
               `id` int NOT NULL AUTO_INCREMENT,
               `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               `olas_id` int NOT NULL DEFAULT '0',
               `execution_time` int NOT NULL,
               `is_active` tinyint NOT NULL DEFAULT '1',
               `entities_id` int NOT NULL DEFAULT '0',
               `is_recursive` tinyint NOT NULL DEFAULT '0',
               `match` char(10) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'see define.php *_MATCHING constant',
               `uuid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
               PRIMARY KEY (`id`),
               KEY `name` (`name`),
               KEY `is_active` (`is_active`),
               KEY `olas_id` (`olas_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_olalevels_tickets')) {
        $query = "CREATE TABLE `sanam_olalevels_tickets` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `tickets_id` int NOT NULL DEFAULT '0',
                  `olalevels_id` int NOT NULL DEFAULT '0',
                  `date` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `tickets_id` (`tickets_id`),
                  KEY `olalevels_id` (`olalevels_id`),
                  KEY `unicity` (`tickets_id`,`olalevels_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);

        $DB->updateOrInsert("sanam_crontasks", [
            'frequency'       => "604800",
            'param'           => null,
            'state'           => "1",
            'mode'            => "1",
            'allowmode'       => "3",
            'hourmin'         => "0",
            'hourmax'         => "24",
            'logs_lifetime'   => "30",
            'lastrun'         => null,
            'lastcode'        => null,
            'comment'         => null,
        ], [
            'itemtype'  => "OlaLevel_Ticket",
            'name'      => "olaticket",
        ]);
    }

    if (!$DB->tableExists('sanam_slms')) {
        // Changing the structure of the table 'sanam_slas'
        $migration->renameTable('sanam_slas', 'sanam_slms');
        $migration->migrationOneTable('sanam_slas');
    }

    // Changing the structure of the table 'sanam_slts'
    if ($DB->tableExists('sanam_slts')) {
        $migration->renameTable('sanam_slts', 'sanam_slas');
        $migration->migrationOneTable('sanam_slts');
        $migration->changeField('sanam_slas', 'slas_id', 'slms_id', 'integer');
        $migration->dropKey('sanam_slas', 'slas_id');
        $migration->addKey('sanam_slas', 'slms_id');
    }

    // Slalevels changes
    if ($DB->fieldExists("sanam_slalevels", "slts_id")) {
        $migration->changeField('sanam_slalevels', 'slts_id', 'slas_id', 'integer');
        $migration->migrationOneTable('sanam_slalevels');
        $migration->dropKey('sanam_slalevels', 'slts_id');
        $migration->addKey('sanam_slalevels', 'slas_id');
    }

    // Ticket changes
    if (!$DB->fieldExists("sanam_tickets", "ola_waiting_duration", false)) {
        $migration->addField(
            "sanam_tickets",
            "ola_waiting_duration",
            "integer",
            ['after' => 'sla_waiting_duration']
        );
        $migration->migrationOneTable('sanam_tickets');
    }

    if (!$DB->fieldExists("sanam_tickets", "olas_tto_id", false)) {
        $migration->addField("sanam_tickets", "olas_tto_id", "integer", ['after' => 'ola_waiting_duration']);
        $migration->migrationOneTable('sanam_tickets');
        $migration->addKey('sanam_tickets', 'olas_tto_id');
    }

    if (!$DB->fieldExists("sanam_tickets", "olas_ttr_id", false)) {
        $migration->addField("sanam_tickets", "olas_ttr_id", "integer", ['after' => 'olas_tto_id']);
        $migration->migrationOneTable('sanam_tickets');
        $migration->addKey('sanam_tickets', 'olas_ttr_id');
    }

    if (!$DB->fieldExists("sanam_tickets", "ttr_olalevels_id", false)) {
        $migration->addField("sanam_tickets", "ttr_olalevels_id", "integer", ['after' => 'olas_ttr_id']);
        $migration->migrationOneTable('sanam_tickets');
    }

    if (!$DB->fieldExists("sanam_tickets", "internal_time_to_resolve", false)) {
        $migration->addField(
            "sanam_tickets",
            "internal_time_to_resolve",
            "datetime",
            ['after' => 'ttr_olalevels_id']
        );
        $migration->migrationOneTable('sanam_tickets');
        $migration->addKey('sanam_tickets', 'internal_time_to_resolve');
    }

    if (!$DB->fieldExists("sanam_tickets", "internal_time_to_own", false)) {
        $migration->addField(
            "sanam_tickets",
            "internal_time_to_own",
            "datetime",
            ['after' => 'internal_time_to_resolve']
        );
        $migration->migrationOneTable('sanam_tickets');
        $migration->addKey('sanam_tickets', 'internal_time_to_own');
    }

    if ($DB->fieldExists("sanam_tickets", "slts_tto_id")) {
        $migration->changeField("sanam_tickets", "slts_tto_id", "slas_tto_id", "integer");
        $migration->migrationOneTable('sanam_tickets');
        $migration->addKey('sanam_tickets', 'slas_tto_id');
        $migration->dropKey('sanam_tickets', 'slts_tto_id');
    }

    if ($DB->fieldExists("sanam_tickets", "slts_ttr_id")) {
        $migration->changeField("sanam_tickets", "slts_ttr_id", "slas_ttr_id", "integer");
        $migration->migrationOneTable('sanam_tickets');
        $migration->addKey('sanam_tickets', 'slas_ttr_id');
        $migration->dropKey('sanam_tickets', 'slts_ttr_id');
    }
    if ($DB->fieldExists("sanam_tickets", "due_date")) {
        $migration->changeField('sanam_tickets', 'due_date', 'time_to_resolve', 'datetime');
        $migration->migrationOneTable('sanam_tickets');
        $migration->dropKey('sanam_tickets', 'due_date');
        $migration->addKey('sanam_tickets', 'time_to_resolve');
    }

    //Change changes
    if ($DB->fieldExists("sanam_changes", "due_date")) {
        $migration->changeField('sanam_changes', 'due_date', 'time_to_resolve', 'datetime');
        $migration->migrationOneTable('sanam_changes');
        $migration->dropKey('sanam_changes', 'due_date');
        $migration->addKey('sanam_changes', 'time_to_resolve');
    }

    //Problem changes
    if ($DB->fieldExists("sanam_problems", "due_date")) {
        $migration->changeField('sanam_problems', 'due_date', 'time_to_resolve', 'datetime');
        $migration->migrationOneTable('sanam_problems');
        $migration->dropKey('sanam_problems', 'due_date');
        $migration->addKey('sanam_problems', 'time_to_resolve');
    }

    // ProfileRights changes
    $DB->update(
        "sanam_profilerights",
        ['name' => "slm"],
        ['name' => "sla"]
    );

    //Sla rules criterias migration
    $DB->update(
        "sanam_rulecriterias",
        ['criteria' => "slas_ttr_id"],
        ['criteria' => "slts_ttr_id"]
    );

    $DB->update(
        "sanam_rulecriterias",
        ['criteria' => "slas_tto_id"],
        ['criteria' => "slts_tto_id"]
    );

    // Sla rules actions migration
    $DB->update(
        "sanam_ruleactions",
        ['field' => "slas_ttr_id"],
        ['field' => "slts_ttr_id"]
    );

    $DB->update(
        "sanam_ruleactions",
        ['field' => "slas_tto_id"],
        ['field' => "slts_tto_id"]
    );

    /************** Auto login **************/
    $migration->addConfig([
        'login_remember_time'      => 604800,
        'login_remember_default'   => 1,
    ]);

    if ($DB->tableExists('sanam_bookmarks')) {
        $migration->renameTable("sanam_bookmarks", "sanam_savedsearches");

        $migration->addField("sanam_savedsearches", "last_execution_time", "int NULL DEFAULT NULL");
        $migration->addField(
            "sanam_savedsearches",
            "do_count",
            "tinyint NOT NULL DEFAULT '2' COMMENT 'Do or do not count results on list display; see SavedSearch::COUNT_* constants'"
        );
        $migration->addField(
            "sanam_savedsearches",
            "last_execution_date",
            "DATETIME NULL DEFAULT NULL"
        );
        $migration->addField("sanam_savedsearches", "counter", "int NOT NULL DEFAULT '0'");
        $migration->migrationOneTable("sanam_savedsearches");
        $migration->addKey("sanam_savedsearches", 'last_execution_time');
        $migration->addKey("sanam_savedsearches", 'do_count');
        $migration->addKey("sanam_savedsearches", 'last_execution_date');
    }
    //ensure do_count is set to AUTO
    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_savedsearches",
            ['do_count' => SavedSearch::COUNT_AUTO],
            [new QueryExpression('true')]
        ),
        'Set count auto on saved searches'
    );

    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_savedsearches",
            ['entities_id' => 0],
            ['entities_id' => -1]
        ),
        'Update entities_id in saved searches'
    );

    if (
        !countElementsInTable(
            'sanam_rules',
            ['sub_type' => 'RuleSoftwareCategory',
                'uuid'     => '500717c8-2bd6e957-53a12b5fd38869.86003425',
            ]
        )
    ) {
        $migration->createRule(
            [
                'name'         => 'Import category from inventory tool',
                'uuid'         => '500717c8-2bd6e957-53a12b5fd38869.86003425',
                'entities_id'  => 0,
                'is_recursive' => 1,
                'sub_type'     => 'RuleSoftwareCategory',
                'match'        => Rule::AND_MATCHING,
                'condition'    => 1,
            ],
            [
                [
                    'criteria'  => 'name',
                    'condition' => '0',
                    'pattern'   => '*',
                ],
            ],
            [
                [
                    'action_type' => 'assign',
                    'field'       => '_import_category',
                    'value'       => '1',
                ],
            ]
        );
    }

    if ($DB->tableExists('sanam_queuedmails')) {
        $migration->renameTable("sanam_queuedmails", "sanam_queuednotifications");
    }

    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_crontasks",
            ['itemtype' => "QueuedNotification"],
            ['itemtype' => "QueuedMail"]
        )
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_crontasks",
            ['name' => "queuednotification"],
            ['name' => "queuedmail"]
        )
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_crontasks",
            ['name' => "queuednotificationclean"],
            ['name' => "queuedmailclean"]
        )
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_profilerights",
            ['name' => "queuednotification"],
            ['name' => "queuedmail"]
        )
    );

    if (isset($current_config['use_mailing']) && !isset($current_config['use_notifications'])) {
        /** Notifications modes */
        $migration->addConfig([
            'use_notifications'                 => $current_config['use_mailing'],
            'notifications_mailing'    => $current_config['use_mailing'],
            'notifications_ajax'       => 0,
            'notifications_ajax_check_interval' => '5',
            'notifications_ajax_sound' => null,
            'notifications_ajax_icon_url'       => '/pics/sanam.png',
        ]);
        $migration->removeConfig(['use_mailing']);
    }

    if (!$DB->tableExists('sanam_notifications_notificationtemplates')) {
        $query = "CREATE TABLE `sanam_notifications_notificationtemplates` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `notifications_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `mode` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'See Notification_NotificationTemplate::MODE_* constants',
                  `notificationtemplates_id` int NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unicity` (`notifications_id`, `mode`, `notificationtemplates_id`),
                  KEY `notifications_id` (`notifications_id`),
                  KEY `notificationtemplates_id` (`notificationtemplates_id`),
                  KEY `mode` (`mode`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if ($DB->fieldExists("sanam_notifications", "mode", false)) {
        // TODO can be done when DB::updateOrInsert() supports SELECT
        $query = "REPLACE INTO `sanam_notifications_notificationtemplates`
                       (`notifications_id`, `mode`, `notificationtemplates_id`)
                       SELECT `id`, `mode`, `notificationtemplates_id`
                       FROM `sanam_notifications`";
        $DB->doQuery($query);

        //migrate any existing mode before removing the field
        $migration->dropField('sanam_notifications', 'mode');
        $migration->dropField('sanam_notifications', 'notificationtemplates_id');

        $migration->migrationOneTable("sanam_notifications");
    }

    $migration->addField(
        'sanam_queuednotifications',
        'mode',
        'varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT \'See Notification_NotificationTemplate::MODE_* constants\''
    );
    $migration->migrationOneTable("sanam_queuednotifications");
    $migration->addKey('sanam_queuednotifications', 'mode');
    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_queuednotifications",
            ['mode' => Notification_NotificationTemplate::MODE_MAIL],
            [true]
        ),
        "9.2 set default mode in queue"
    );

    $migration->addPostQuery(
        $DB->buildUpdate(
            "sanam_notifications_notificationtemplates",
            ['mode' => Notification_NotificationTemplate::MODE_MAIL],
            ['mode' => "mail"]
        ),
        "9.2 set default mode in notifications templates"
    );

    // Migration Bookmark -> SavedSearch_Alert
    //TRANS: %s is the table or item to migrate
    if ($DB->tableExists('sanam_bookmarks_users')) {
        $migration->renameTable("sanam_bookmarks_users", "sanam_savedsearches_users");
        $migration->changeField(
            'sanam_savedsearches_users',
            'bookmarks_id',
            'savedsearches_id',
            'int NOT NULL DEFAULT "0"'
        );
    }

    if (!$DB->tableExists('sanam_savedsearches_alerts')) {
        $query = "CREATE TABLE `sanam_savedsearches_alerts` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `savedsearches_id` int NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `is_active` tinyint NOT NULL DEFAULT '0',
                  `operator` tinyint NOT NULL,
                  `value` int NOT NULL,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `is_active` (`is_active`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`),
                  UNIQUE KEY `unicity` (`savedsearches_id`,`operator`, `value`)
                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    $migration->displayMessage(sprintf(__('Data migration - %s'), 'sanam_displaypreferences'));

    $ADDTODISPLAYPREF['SavedSearch'] = [8, 9, 3, 10, 11];
    foreach ($ADDTODISPLAYPREF as $type => $tab) {
        $rank = 1;
        foreach ($tab as $newval) {
            $DB->updateOrInsert("sanam_displaypreferences", [
                'rank'      => $rank++,
            ], [
                'users_id'  => "0",
                'itemtype'  => $type,
                'num'       => $newval,
            ]);
        }
    }

    if (countElementsInTable('sanam_logs') < 2000000) {
        //add index only if this sounds... possible.
        $migration->addKey("sanam_logs", "id_search_option");
    } else {
        //Just display a Warning to the user.
        $migration->addWarningMessage("An index must be added in the 'id_search_option' field "
         . "of the 'sanam_logs table'; but your sanam_logs table is "
         . "too huge. You'll have to add it on your database "
         . "with the following query:\n"
         . "'ALTER TABLE sanam_logs ADD INDEX id_search_option(id_search_option);'");
    }

    // count cron task
    if (
        !countElementsInTable(
            'sanam_crontasks',
            ['itemtype' => 'SavedSearch', 'name' => 'countAll']
        )
    ) {
        $DB->insert(
            "sanam_crontasks",
            [
                'itemtype'        => "SavedSearch",
                'name'            => "countAll",
                'frequency'       => "604800",
                'param'           => null,
                'state'           => "0",
                'mode'            => "1",
                'allowmode'       => "3",
                'hourmin'         => "0",
                'hourmax'         => "24",
                'logs_lifetime'   => "30",
                'lastrun'         => null,
                'lastcode'        => null,
                'comment'         => null,
            ]
        );
    };

    // alerts cron task
    if (
        !countElementsInTable(
            'sanam_crontasks',
            ['itemtype' => 'SavedSearch_Alert', 'name' => 'savedsearchesalerts']
        )
    ) {
        $DB->insert(
            "sanam_crontasks",
            [
                'itemtype'        => "SavedSearch_Alert",
                'name'            => "savedsearchesalerts",
                'frequency'       => "86400",
                'param'           => null,
                'state'           => "0",
                'mode'            => "1",
                'allowmode'       => "3",
                'hourmin'         => "0",
                'hourmax'         => "24",
                'logs_lifetime'   => "30",
                'lastrun'         => null,
                'lastcode'        => null,
                'comment'         => null,
            ]
        );
    }

    if (
        !countElementsInTable(
            'sanam_notifications',
            ['itemtype' => 'SavedSearch_Alert']
        )
    ) {
        $DB->insert(
            "sanam_notifications",
            [
                'id'              => null,
                'name'            => "Saved searches",
                'entities_id'     => "0",
                'itemtype'        => "SavedSearch_Alert",
                'event'           => "alert",
                'comment'         => "",
                'is_recursive'    => "1",
                'is_active'       => "1",
                'date_creation'   => new QueryExpression("NOW()"),
                'date_mod'        => new QueryExpression("NOW()"),
            ]
        );
        $notid = $DB->insertId();

        $DB->insert(
            "sanam_notificationtemplates",
            [
                'name'            => "Saved searches alerts",
                'itemtype'        => "SavedSearch_Alert",
                'date_mod'        => new QueryExpression("NOW()"),
            ]
        );
        $nottid = $DB->insertId();

        $where =  [
            'notifications_id'         => $notid,
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            'notificationtemplates_id' => $nottid,
        ];
        if (countElementsInTable('sanam_notifications_notificationtemplates', $where)) {
            $DB->updateOrInsert("sanam_notifications_notificationtemplates", [
                'id'                       => null,
            ], [
                'notifications_id'         => $notid,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $nottid,
            ]);
        }

        $DB->insert(
            "sanam_notificationtargets",
            [
                'id'               => null,
                'items_id'         => "19",
                'type'             => "1",
                'notifications_id' => $notid,
            ]
        );

        $query = "INSERT INTO `sanam_notificationtemplatetranslations`
                       (`notificationtemplates_id`, `language`,`subject`,
                              `content_text`,
                              `content_html`)
                     VALUES ($notid, '', '##savedsearch.action## ##savedsearch.name##',
                     '##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##

##savedsearch.message##

##lang.savedsearch.url##
##savedsearch.url##

Regards,',
                     '&lt;table&gt;
                     &lt;tbody&gt;
                     &lt;tr&gt;&lt;th colspan=\"2\"&gt;&lt;a href=\"##savedsearch.url##\"&gt;##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
                     &lt;tr&gt;&lt;td colspan=\"2\"&gt;&lt;a href=\"##savedsearch.url##\"&gt;##savedsearch.message##&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;
                     &lt;tr&gt;
                     &lt;td&gt;##lang.savedsearch.url##&lt;/td&gt;
                     &lt;td&gt;##savedsearch.url##&lt;/td&gt;
                     &lt;/tr&gt;
                     &lt;/tbody&gt;
                     &lt;/table&gt;
                     &lt;p&gt;&lt;span style=\"font-size: small;\"&gt;Hello &lt;br /&gt;Regards,&lt;/span&gt;&lt;/p&gt;')";

        $DB->doQuery($query);
    }

    // Create a dedicated token for api
    if (!$DB->fieldExists('sanam_users', 'api_token')) {
        $migration->addField('sanam_users', 'api_token', 'string', ['after' => 'personal_token_date']);
        $migration->addField('sanam_users', 'api_token_date', 'datetime', ['after' => 'api_token']);
        $migration->addWarningMessage(
            "Api users tokens has been reset, if you use REST/XMLRPC api with personal token for authentication, please reset your user's token."
        );
    }

    if (!$DB->tableExists('sanam_items_operatingsystems')) {
        $query = "CREATE TABLE `sanam_items_operatingsystems` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `items_id` int NOT NULL DEFAULT '0',
                  `itemtype` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `operatingsystems_id` int NOT NULL DEFAULT '0',
                  `operatingsystemversions_id` int NOT NULL DEFAULT '0',
                  `operatingsystemservicepacks_id` int NOT NULL DEFAULT '0',
                  `operatingsystemarchitectures_id` int NOT NULL DEFAULT '0',
                  `operatingsystemkernelversions_id` int NOT NULL DEFAULT '0',
                  `license_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `license_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `operatingsystemeditions_id` int NOT NULL DEFAULT '0',
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `items_id` (`items_id`),
                  KEY `item` (`itemtype`,`items_id`),
                  KEY `operatingsystems_id` (`operatingsystems_id`),
                  KEY `operatingsystemservicepacks_id` (`operatingsystemservicepacks_id`),
                  KEY `operatingsystemversions_id` (`operatingsystemversions_id`),
                  KEY `operatingsystemarchitectures_id` (`operatingsystemarchitectures_id`),
                  KEY `operatingsystemkernelversions_id` (`operatingsystemkernelversions_id`),
                  KEY `operatingsystemeditions_id` (`operatingsystemeditions_id`),
                  UNIQUE KEY `unicity`(`items_id`,`itemtype`, `operatingsystems_id`,
                                       `operatingsystemarchitectures_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_operatingsystemkernels')) {
        $query = "CREATE TABLE `sanam_operatingsystemkernels` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_operatingsystemkernelversions')) {
        $query = "CREATE TABLE `sanam_operatingsystemkernelversions` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `operatingsystemkernels_id` int NOT NULL DEFAULT '0',
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `operatingsystemkernels_id` (`operatingsystemkernels_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_operatingsystemeditions')) {
        $query = "CREATE TABLE `sanam_operatingsystemeditions` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if ($DB->fieldExists('sanam_computers', 'operatingsystems_id')) {
        //migrate data from computers table, and drop old fields
        // TODO can be done when DB::updateOrInsert() supports SELECT
        $query = "REPLACE INTO `sanam_items_operatingsystems`
                       (`itemtype`, `items_id`, `operatingsystems_id`, `operatingsystemversions_id`,
                        `operatingsystemservicepacks_id`, `operatingsystemarchitectures_id`,
                        `license_number`, `license_id`)
                       SELECT 'Computer', `id`, `operatingsystems_id`, `operatingsystemversions_id`,
                              `operatingsystemservicepacks_id`, `operatingsystemarchitectures_id`,
                              `os_license_number`, `os_licenseid`
                FROM `sanam_computers`
                WHERE `operatingsystems_id` != 0
                      OR `operatingsystemservicepacks_id` != 0
                      OR `operatingsystemarchitectures_id` != 0
                      OR `os_license_number` IS NOT NULL
                      OR `os_kernel_version` IS NOT NULL
                      OR `os_licenseid` IS NOT NULL";
        $DB->doQuery($query);

        //migrate kernel versions.
        $mapping = [];
        foreach (
            $DB->request(['SELECT' => ['id', 'os_kernel_version'],
                'FROM'   => 'sanam_computers',
                'NOT'   => ['os_kernel_version' => null],
            ]) as $data
        ) {
            $key = md5($data['os_kernel_version']);
            if (!isset($mapping[$key])) {
                $mapping[$key] = [];
            }
            $DB->insert('sanam_operatingsystemkernelversions', ['name' => $data['os_kernel_version']]);
            $kver_id = $DB->insertId();
            $mapping[$key][$data['id']] = $kver_id;
        }

        foreach ($mapping as $map) {
            foreach ($map as $computers_id => $kver_id) {
                $DB->update(
                    "sanam_items_operatingsystems",
                    ['operatingsystemkernelversions_id' => $kver_id],
                    [
                        'itemtype' => "Computer",
                        'items_id' => $computers_id,
                    ]
                );
            }
        }

        $migration->dropKey('sanam_computers', 'operatingsystems_id');
        $migration->dropField('sanam_computers', 'operatingsystems_id');
        $migration->dropKey('sanam_computers', 'operatingsystemservicepacks_id');
        $migration->dropField('sanam_computers', 'operatingsystemservicepacks_id');
        $migration->dropKey('sanam_computers', 'operatingsystemversions_id');
        $migration->dropField('sanam_computers', 'operatingsystemversions_id');
        $migration->dropKey('sanam_computers', 'operatingsystemarchitectures_id');
        $migration->dropField('sanam_computers', 'operatingsystemarchitectures_id');
        $migration->dropField('sanam_computers', 'os_license_number');
        $migration->dropField('sanam_computers', 'os_licenseid');
        $migration->dropField('sanam_computers', 'os_kernel_version');
    }

    if (!$DB->fieldExists('sanam_items_operatingsystems', 'is_deleted')) {
        $migration->addField(
            'sanam_items_operatingsystems',
            'is_deleted',
            "tinyint NOT NULL DEFAULT '0'"
        );
        $migration->addKey('sanam_items_operatingsystems', 'is_deleted');
    }

    if (!$DB->fieldExists('sanam_items_operatingsystems', 'is_dynamic')) {
        $migration->addField(
            'sanam_items_operatingsystems',
            'is_dynamic',
            "tinyint NOT NULL DEFAULT '0'"
        );
        $migration->addKey('sanam_items_operatingsystems', 'is_dynamic');
    }

    if (!$DB->fieldExists('sanam_items_operatingsystems', 'entities_id')) {
        $migration->addField(
            'sanam_items_operatingsystems',
            'entities_id',
            "int NOT NULL DEFAULT '0'"
        );
        $migration->addKey('sanam_items_operatingsystems', 'entities_id');
    }

    //add db version
    $migration->addConfig(['dbversion' => '9.1.3']);

    // Add certificates management
    if (!$DB->tableExists('sanam_certificates')) {
        $query = "CREATE TABLE `sanam_certificates` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) COLLATE utf8_unicode_ci  DEFAULT NULL,
        `serial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        `otherserial` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        `entities_id` INT NOT NULL DEFAULT '0',
        `is_recursive` TINYINT NOT NULL DEFAULT '0',
        `comment` text COLLATE utf8_unicode_ci,
        `is_deleted` tinyint NOT NULL DEFAULT '0',
        `is_template` tinyint NOT NULL DEFAULT '0',
        `template_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        `certificatetypes_id`  INT NOT NULL DEFAULT '0' COMMENT 'RELATION to sanam_certificatetypes (id)',
        `dns_name` VARCHAR(255) COLLATE utf8_unicode_ci  DEFAULT NULL,
        `dns_suffix` VARCHAR(255) COLLATE utf8_unicode_ci  DEFAULT NULL,
        `users_id_tech` INT NOT NULL DEFAULT '0' COMMENT 'RELATION to sanam_users (id)',
        `groups_id_tech` INT NOT NULL DEFAULT '0' COMMENT 'RELATION to sanam_groups (id)',
        `locations_id` INT NOT NULL DEFAULT '0' COMMENT 'RELATION to sanam_locations (id)',
        `manufacturers_id` INT NOT NULL DEFAULT '0' COMMENT 'RELATION to sanam_manufacturers (id)',
        `users_id` int NOT NULL DEFAULT '0',
        `groups_id` int NOT NULL DEFAULT '0',
        `is_autosign` TINYINT NOT NULL DEFAULT '0',
        `date_expiration` DATE DEFAULT NULL,
        `states_id` INT NOT NULL DEFAULT '0' COMMENT 'RELATION to states (id)',
        `command` TEXT COLLATE utf8_unicode_ci,
        `certificate_request` TEXT COLLATE utf8_unicode_ci,
        `certificate_item` TEXT COLLATE utf8_unicode_ci,
        `date_creation` DATETIME DEFAULT NULL,
        `date_mod` DATETIME DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `entities_id` (`entities_id`),
        KEY `is_template` (`is_template`),
        KEY `is_deleted` (`is_deleted`),
        KEY `certificatetypes_id` (`certificatetypes_id`),
        KEY `users_id_tech` (`users_id_tech`),
        KEY `groups_id_tech` (`groups_id_tech`),
        KEY `groups_id` (`groups_id`),
        KEY `users_id` (`users_id`),
        KEY `locations_id` (`locations_id`),
        KEY `manufacturers_id` (`manufacturers_id`),
        KEY `states_id` (`states_id`),
        KEY `date_creation` (`date_creation`),
        KEY `date_mod` (`date_mod`)
      ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_certificates_items')) {
        $query = "CREATE TABLE `sanam_certificates_items` (
           `id` INT NOT NULL AUTO_INCREMENT,
           `certificates_id` INT NOT NULL DEFAULT '0',
           `items_id` INT NOT NULL DEFAULT '0' COMMENT 'RELATION to various tables, according to itemtype (id)',
           `itemtype` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'see .class.php file',
           `date_creation` DATETIME DEFAULT NULL,
           `date_mod` DATETIME DEFAULT NULL,
           PRIMARY KEY (`id`),
           UNIQUE KEY `unicity` (`certificates_id`, `itemtype`, `items_id`),
           KEY `device` (`items_id`, `itemtype`),
           KEY `item` (`itemtype`, `items_id`),
           KEY `date_creation` (`date_creation`),
           KEY `date_mod` (`date_mod`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_certificatetypes')) {
        $query = "CREATE TABLE `sanam_certificatetypes` (
           `id` INT NOT NULL AUTO_INCREMENT,
           `entities_id` INT NOT NULL DEFAULT '0',
           `is_recursive` TINYINT NOT NULL DEFAULT '0',
           `name` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
           `comment` TEXT COLLATE utf8_unicode_ci,
           `date_creation` DATETIME DEFAULT NULL,
           `date_mod` DATETIME DEFAULT NULL,
           PRIMARY KEY (`id`),
           KEY `entities_id` (`entities_id`),
           KEY `is_recursive` (`is_recursive`),
           KEY `name` (`name`),
           KEY `date_creation` (`date_creation`),
           KEY `date_mod` (`date_mod`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (countElementsInTable("sanam_profilerights", ['name' => 'certificate']) == 0) {
        //new right for certificate
        //give full rights to profiles having config right
        $prights = $DB->request(['FROM' => 'sanam_profilerights', 'WHERE' => ['name' => 'config']]);
        foreach ($prights as $profrights) {
            if ($profrights['rights'] === (READ | UPDATE)) {
                $rightValue = CREATE | READ | UPDATE | DELETE  | PURGE | READNOTE | UPDATENOTE | UNLOCK;
            } else {
                $rightValue = 0;
            }

            $DB->insert(
                "sanam_profilerights",
                [
                    'id'           => null,
                    'profiles_id'  => $profrights['profiles_id'],
                    'name'         => "certificate",
                    'rights'       => $rightValue,
                ]
            );
        }
    }

    // add alert for certificates
    $migration->addField(
        "sanam_entities",
        'use_certificates_alert',
        "integer",
        ['value' => -2,
            'after' => 'send_licenses_alert_before_delay',
        ]
    );
    $migration->addField(
        "sanam_entities",
        'send_certificates_alert_before_delay',
        "integer",
        ['value'     => -2,
            'after'     => 'use_certificates_alert',
            'update'    => '0', // No delay for root entity
            'condition' => 'WHERE `id` = 0',
        ]
    );
    $migration->addCrontask(
        'Certificate',
        'certificate',
        DAY_TIMESTAMP,
        options: [
            'mode' => 1, // CronTask::MODE_INTERNAL
        ]
    );
    if (!countElementsInTable('sanam_notifications', ['itemtype' => 'Certificate'])) {
        $DB->insert(
            "sanam_notifications",
            [
                'id'              => null,
                'name'            => "Certificates",
                'entities_id'     => "0",
                'itemtype'        => "Certificate",
                'event'           => "alert",
                'comment'         => "",
                'is_recursive'    => "1",
                'is_active'       => "1",
                'date_creation'   => new QueryExpression("NOW()"),
                'date_mod'        => new QueryExpression("NOW()"),
            ]
        );
        $notid = $DB->insertId();

        $DB->insert(
            "sanam_notificationtemplates",
            [
                'name'      => "Certificates",
                'itemtype'  => "Certificate",
                'date_mod'  => new QueryExpression("NOW()"),
            ]
        );
        $nottid = $DB->insertId();

        $where = [
            'notifications_id'         => $notid,
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            'notificationtemplates_id' => $nottid,
        ];
        if (!countElementsInTable('sanam_notifications_notificationtemplates', $where)) {
            $DB->updateOrInsert("sanam_notifications_notificationtemplates", [
                'id'                       => null,
            ], [
                'notifications_id'         => $notid,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $nottid,
            ]);
        }

        $query = "INSERT INTO `sanam_notificationtemplatetranslations`
                  (`notificationtemplates_id`, `language`, `subject`, `content_text`, `content_html`)
                VALUES ($notid, '', '##certificate.action##  ##certificate.entity##',
                        '##lang.certificate.entity## : ##certificate.entity##

##FOREACHcertificates##

##lang.certificate.serial## : ##certificate.serial##

##lang.certificate.expirationdate## : ##certificate.expirationdate##

##certificate.url##
 ##ENDFOREACHcertificates##','&lt;p&gt;
##lang.certificate.entity## : ##certificate.entity##&lt;br /&gt;
##FOREACHcertificates##
&lt;br /&gt;##lang.certificate.name## : ##certificate.name##&lt;br /&gt;
##lang.certificate.serial## : ##certificate.serial##&lt;br /&gt;
##lang.certificate.expirationdate## : ##certificate.expirationdate##
&lt;br /&gt; &lt;a href=\"##certificate.url##\"&gt; ##certificate.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHcertificates##&lt;/p&gt;')";

        $DB->doQuery($query);

        $DB->insert(
            "sanam_notificationtargets",
            [
                'id'                 => null,
                'notifications_id'   => $notid,
                'type'               => Notification::USER_TYPE,
                'items_id'           => Notification::ITEM_TECH_IN_CHARGE,
            ]
        );
        $DB->insert(
            "sanam_notificationtargets",
            [
                'id'                 => null,
                'notifications_id'   => $notid,
                'type'               => Notification::USER_TYPE,
                'items_id'           => Notification::ITEM_TECH_GROUP_IN_CHARGE,
            ]
        );
    }

    /************** Simcard component **************/
    $migration->addField("sanam_states", "is_visible_line", "bool", ["after" => "is_visible_softwarelicense"]);

    if (!$DB->tableExists('sanam_lineoperators')) {
        $query = "CREATE TABLE IF NOT EXISTS `sanam_lineoperators` (
                   `id` int NOT NULL AUTO_INCREMENT,
                   `name` varchar(255) NOT NULL DEFAULT '',
                   `comment` text COLLATE utf8_unicode_ci,
                   `mcc` int DEFAULT NULL,
                   `mnc` int DEFAULT NULL,
                   `entities_id`      INT NOT NULL DEFAULT 0,
                   `is_recursive`     TINYINT NOT NULL DEFAULT 0,
                   `date_mod` datetime DEFAULT NULL,
                   `date_creation` datetime DEFAULT NULL,
                   PRIMARY KEY (`id`),
                   KEY `name` (`name`),
                   KEY `entities_id`  (`entities_id`),
                   KEY `is_recursive` (`is_recursive`),
                   KEY `date_mod` (`date_mod`),
                   KEY `date_creation` (`date_creation`),
                   UNIQUE KEY `unicity` (`mcc`,`mnc`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_linetypes')) {
        $query = "CREATE TABLE IF NOT EXISTS `sanam_linetypes` (
         `id` int NOT NULL AUTO_INCREMENT,
         `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
         `comment` text COLLATE utf8_unicode_ci,
         `date_mod` datetime DEFAULT NULL,
         `date_creation` datetime DEFAULT NULL,
         PRIMARY KEY (`id`),
         KEY `name` (`name`),
         KEY `date_mod` (`date_mod`),
         KEY `date_creation` (`date_creation`)
         ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_lines')) {
        $query = "CREATE TABLE `sanam_lines` (
            `id`                   INT NOT NULL auto_increment,
            `name`                 VARCHAR(255) NOT NULL DEFAULT '',
            `entities_id`          INT NOT NULL DEFAULT 0,
            `is_recursive`         TINYINT NOT NULL DEFAULT 0,
            `is_deleted`           TINYINT NOT NULL DEFAULT 0,
            `caller_num`           VARCHAR(255) NOT NULL DEFAULT '',
            `caller_name`          VARCHAR(255) NOT NULL DEFAULT '',
            `users_id`             INT NOT NULL DEFAULT 0,
            `groups_id`            INT NOT NULL DEFAULT 0,
            `lineoperators_id`     INT NOT NULL DEFAULT 0,
            `locations_id`         INT NOT NULL DEFAULT '0',
            `states_id`            INT NOT NULL DEFAULT '0',
            `linetypes_id`         INT NOT NULL DEFAULT '0',
            `date_creation`        DATETIME DEFAULT NULL,
            `date_mod`             DATETIME DEFAULT NULL,
            `comment`              TEXT DEFAULT NULL,
            PRIMARY KEY            (`id`),
            KEY `entities_id`      (`entities_id`),
            KEY `is_recursive`     (`is_recursive`),
            KEY `users_id`         (`users_id`),
            KEY `lineoperators_id` (`lineoperators_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_devicesimcardtypes')) {
        $query = "CREATE TABLE IF NOT EXISTS `sanam_devicesimcardtypes` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL DEFAULT '',
                  `comment` text COLLATE utf8_unicode_ci,
                  `date_mod` datetime DEFAULT NULL,
                  `date_creation` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `name` (`name`),
                  KEY `date_mod` (`date_mod`),
                  KEY `date_creation` (`date_creation`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    if (!countElementsInTable('sanam_devicesimcardtypes', ['name' => 'Full SIM'])) {
        $DB->insert("sanam_devicesimcardtypes", [
            'id'              => null,
            'name'            => "Full SIM",
            'comment'         => null,
            'date_mod'        => null,
            'date_creation'   => null,
        ]);
    }
    if (!countElementsInTable('sanam_devicesimcardtypes', ['name' => 'Mini SIM'])) {
        $DB->insert("sanam_devicesimcardtypes", [
            'id'              => null,
            'name'            => "Mini SIM",
            'comment'         => null,
            'date_mod'        => null,
            'date_creation'   => null,
        ]);
    }
    if (!countElementsInTable('sanam_devicesimcardtypes', ['name' => 'Micro SIM'])) {
        $DB->insert("sanam_devicesimcardtypes", [
            'id'              => null,
            'name'            => "Micro SIM",
            'comment'         => null,
            'date_mod'        => null,
            'date_creation'   => null,
        ]);
    }
    if (!countElementsInTable('sanam_devicesimcardtypes', ['name' => 'Nano SIM'])) {
        $DB->insert("sanam_devicesimcardtypes", [
            'id'              => null,
            'name'            => "Nano SIM",
            'comment'         => null,
            'date_mod'        => null,
            'date_creation'   => null,
        ]);
    }

    if (!$DB->tableExists('sanam_devicesimcards')) {
        $query = "CREATE TABLE IF NOT EXISTS `sanam_devicesimcards` (
               `id` int NOT NULL AUTO_INCREMENT,
               `designation` varchar(255) DEFAULT NULL,
               `comment` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL,
               `entities_id` int NOT NULL DEFAULT '0',
               `is_recursive` tinyint NOT NULL DEFAULT '0',
               `manufacturers_id` int NOT NULL DEFAULT '0',
               `voltage` int DEFAULT NULL,
               `devicesimcardtypes_id` int NOT NULL DEFAULT '0',
               `date_mod` datetime DEFAULT NULL,
               `date_creation` datetime DEFAULT NULL,
               `allow_voip` tinyint NOT NULL DEFAULT '0',
               PRIMARY KEY (`id`),
               KEY `designation` (`designation`),
               KEY `entities_id` (`entities_id`),
               KEY `is_recursive` (`is_recursive`),
               KEY `devicesimcardtypes_id` (`devicesimcardtypes_id`),
               KEY `date_mod` (`date_mod`),
               KEY `date_creation` (`date_creation`),
               KEY `manufacturers_id` (`manufacturers_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (!$DB->tableExists('sanam_items_devicesimcards')) {
        $query = "CREATE TABLE IF NOT EXISTS `sanam_items_devicesimcards` (
                  `id` int NOT NULL AUTO_INCREMENT,
                  `items_id` int NOT NULL DEFAULT '0' COMMENT 'RELATION to various table, according to itemtype (id)',
                  `itemtype` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                  `devicesimcards_id` int NOT NULL DEFAULT '0',
                  `is_deleted` tinyint NOT NULL DEFAULT '0',
                  `is_dynamic` tinyint NOT NULL DEFAULT '0',
                  `entities_id` int NOT NULL DEFAULT '0',
                  `serial` varchar(255) NULL DEFAULT NULL,
                  `otherserial` varchar(255) NULL DEFAULT NULL,
                  `states_id` int NOT NULL DEFAULT '0',
                  `locations_id` int NOT NULL DEFAULT '0',
                  `lines_id` int NOT NULL DEFAULT '0',
                  `pin` varchar(255) NOT NULL DEFAULT '',
                  `pin2` varchar(255) NOT NULL DEFAULT '',
                  `puk` varchar(255) NOT NULL DEFAULT '',
                  `puk2` varchar(255) NOT NULL DEFAULT '',
                  PRIMARY KEY (`id`),
                  KEY `item` (`itemtype`,`items_id`),
                  KEY `devicesimcards_id` (`devicesimcards_id`),
                  KEY `is_deleted` (`is_deleted`),
                  KEY `is_dynamic` (`is_dynamic`),
                  KEY `entities_id` (`entities_id`),
                  KEY `serial` (`serial`),
                  KEY `otherserial` (`otherserial`),
                  KEY `states_id` (`states_id`),
                  KEY `locations_id` (`locations_id`),
                  KEY `lines_id` (`lines_id`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $DB->doQuery($query);
    }

    if (countElementsInTable("sanam_profilerights", ['name' => 'line']) == 0) {
        //new right for line
        //give full rights to profiles having config right
        $prights = $DB->request(['FROM' => 'sanam_profilerights', 'WHERE' => ['name' => 'config']]);
        foreach ($prights as $profrights) {
            if ($profrights['rights'] === (READ | UPDATE)) {
                $rightValue = CREATE | READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE;
            } else {
                $rightValue = 0;
            }
            $DB->insert(
                "sanam_profilerights",
                [
                    'id'           => null,
                    'profiles_id'  => $profrights['profiles_id'],
                    'name'         => "line",
                    'rights'       => $rightValue,
                ]
            );
        }
    }

    if (countElementsInTable("sanam_profilerights", ['name' => 'lineoperator']) == 0) {
        //new right for lineoperator
        //give full rights to profiles having config right
        $prights = $DB->request(['FROM' => 'sanam_profilerights', 'WHERE' => ['name' => 'config']]);
        foreach ($prights as $profrights) {
            if ($profrights['rights'] === (READ | UPDATE)) {
                $rightValue = CREATE | READ | UPDATE | DELETE | PURGE;
            } else {
                $rightValue = 0;
            }
            $DB->insert(
                "sanam_profilerights",
                [
                    'id'           => null,
                    'profiles_id'  => $profrights['profiles_id'],
                    'name'         => "lineoperator",
                    'rights'       => $rightValue,
                ]
            );
        }
    }

    if (countElementsInTable("sanam_profilerights", ['name' => 'devicesimcard_pinpuk']) == 0) {
        //new right for simcard pin and puk
        //give full rights to profiles having config right
        $prights = $DB->request(['FROM' => 'sanam_profilerights', 'WHERE' => ['name' => 'config']]);
        foreach ($prights as $profrights) {
            if ($profrights['rights'] === (READ | UPDATE)) {
                $rightValue = READ | UPDATE;
            } else {
                $rightValue = 0;
            }
            $DB->insert(
                "sanam_profilerights",
                [
                    'id'           => null,
                    'profiles_id'  => $profrights['profiles_id'],
                    'name'         => "devicesimcard_pinpuk",
                    'rights'       => $rightValue,
                ]
            );
        }
    }

    //Firmware for phones
    if ($DB->fieldExists('sanam_phones', 'firmware')) {
        $iterator = $DB->request([
            'SELECT' => ['id', 'firmware'],
            'FROM'   => 'sanam_phones',
            'NOT'    => ['firmware' => null],
        ]);

        $firmwares = [];
        foreach ($iterator as $row) {
            if (!isset($firmwares[$row['firmware']])) {
                $firmware_result = $DB->request([
                    'FROM' => 'sanam_devicefirmwares',
                    'WHERE' => ['designation' => $row['firmware']],
                ]);
                if ($firmware_result->count() === 1) {
                    $firmwares[$row['firmware']] = $firmware_result->current()['id'];
                } else {
                    $DB->insert(
                        'sanam_devicefirmwares',
                        [
                            'designation'              => $row['firmware'],
                            'devicefirmwaretypes_id'   => '3', //type "firmware"
                        ]
                    );
                    $firmwares[$row['firmware']] = $DB->insertId();
                }
            }

            //add link
            $DB->insert(
                'sanam_items_devicefirmwares',
                [
                    'itemtype'           => 'Phone',
                    'items_id'           => $row['id'],
                    'devicefirmwares_id' => $firmwares[$row['firmware']],
                ]
            );
        }

        $migration->dropField('sanam_phones', 'firmware');
    }

    //Firmware for network equipements
    if ($DB->tableExists('sanam_networkequipmentfirmwares')) {
        $mapping = [];
        $iterator = $DB->request(['FROM' => 'sanam_networkequipmentfirmwares']);
        foreach ($iterator as $row) {
            $DB->insert(
                'sanam_devicefirmwares',
                [
                    'designation'              => $row['name'],
                    'comment'                  => $row['comment'],
                    'devicefirmwaretypes_id'   => 3, //type "Firmware"
                    'date_creation'            => $row['date_creation'],
                    'date_mod'                 => $row['date_mod'],
                ]
            );
            $mapping[$row['id']] = $DB->insertId();
        }

        $iterator = $DB->request(['FROM' => 'sanam_networkequipments']);
        foreach ($iterator as $row) {
            if (isset($mapping[$row['networkequipmentfirmwares_id']])) {
                $DB->insert(
                    'sanam_items_devicefirmwares',
                    [
                        'itemtype'           => 'NetworkEquipment',
                        'items_id'           => $row['id'],
                        'devicefirmwares_id' => $mapping[$row['networkequipmentfirmwares_id']],
                    ]
                );
            }
        }

        $migration->dropKey('sanam_networkequipments', 'networkequipmentfirmwares_id');
        $migration->dropField('sanam_networkequipments', 'networkequipmentfirmwares_id');
        $migration->dropTable('sanam_networkequipmentfirmwares');
    }

    // add projecttemplate
    if (!$DB->fieldExists('sanam_projects', 'projecttemplates_id')) {
        $migration->addField("sanam_projects", "projecttemplates_id", "integer");
        $migration->addField("sanam_projects", "is_template", "bool");
        $migration->addField("sanam_projects", "template_name", "string");
        $migration->addKey("sanam_projects", "projecttemplates_id");
    }

    if (!$DB->fieldExists('sanam_projecttasks', 'projecttasktemplates_id')) {
        $migration->addField("sanam_projecttasks", "projecttasktemplates_id", "integer");
        $migration->addField("sanam_projecttasks", "is_template", "bool");
        $migration->addField("sanam_projecttasks", "template_name", "string");
        $migration->addKey("sanam_projecttasks", "projecttasktemplates_id");
    }

    if (!$DB->tableExists('sanam_projecttasktemplates')) {
        $query = "CREATE TABLE `sanam_projecttasktemplates` (
                       `id` int NOT NULL AUTO_INCREMENT,
                       `entities_id` int NOT NULL DEFAULT '0',
                       `is_recursive` tinyint NOT NULL DEFAULT '0',
                       `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                       `description` longtext COLLATE utf8_unicode_ci,
                       `comment` longtext COLLATE utf8_unicode_ci,
                       `projects_id` int NOT NULL DEFAULT '0',
                       `projecttasks_id` int NOT NULL DEFAULT '0',
                       `plan_start_date` datetime DEFAULT NULL,
                       `plan_end_date` datetime DEFAULT NULL,
                       `real_start_date` datetime DEFAULT NULL,
                       `real_end_date` datetime DEFAULT NULL,
                       `planned_duration` int NOT NULL DEFAULT '0',
                       `effective_duration` int NOT NULL DEFAULT '0',
                       `projectstates_id` int NOT NULL DEFAULT '0',
                       `projecttasktypes_id` int NOT NULL DEFAULT '0',
                       `users_id` int NOT NULL DEFAULT '0',
                       `percent_done` int NOT NULL DEFAULT '0',
                       `is_milestone` tinyint NOT NULL DEFAULT '0',
                       `comments` text COLLATE utf8_unicode_ci,
                       `date_mod` datetime DEFAULT NULL,
                       `date_creation` datetime DEFAULT NULL,
                       PRIMARY KEY (`id`),
                       KEY `name` (`name`),
                       KEY `entities_id` (`entities_id`),
                       KEY `is_recursive` (`is_recursive`),
                       KEY `projects_id` (`projects_id`),
                       KEY `projecttasks_id` (`projecttasks_id`),
                       KEY `date_creation` (`date_creation`),
                       KEY `date_mod` (`date_mod`),
                       KEY `users_id` (`users_id`),
                       KEY `plan_start_date` (`plan_start_date`),
                       KEY `plan_end_date` (`plan_end_date`),
                       KEY `real_start_date` (`real_start_date`),
                       KEY `real_end_date` (`real_end_date`),
                       KEY `percent_done` (`percent_done`),
                       KEY `projectstates_id` (`projectstates_id`),
                       KEY `projecttasktypes_id` (`projecttasktypes_id`),
                       KEY `is_milestone` (`is_milestone`)
                     ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $DB->doQuery($query);
    }

    //add editor in followupps
    if (!$DB->fieldExists('sanam_ticketfollowups', 'users_id_editor')) {
        $migration->addField("sanam_ticketfollowups", "users_id_editor", "int NOT NULL DEFAULT '0'", ['after' => 'users_id']);
        $migration->addKey("sanam_ticketfollowups", "users_id_editor");
    }

    //add editor in *tasks
    if (!$DB->fieldExists('sanam_tickettasks', 'users_id_editor')) {
        $migration->addField("sanam_tickettasks", "users_id_editor", "int NOT NULL DEFAULT '0'", ['after' => 'users_id']);
        $migration->addKey("sanam_tickettasks", "users_id_editor");
    }
    if (!$DB->fieldExists('sanam_changetasks', 'users_id_editor')) {
        $migration->addField("sanam_changetasks", "users_id_editor", "int NOT NULL DEFAULT '0'", ['after' => 'users_id']);
        $migration->addKey("sanam_changetasks", "users_id_editor");
    }
    if (!$DB->fieldExists('sanam_problemtasks', 'users_id_editor')) {
        $migration->addField("sanam_problemtasks", "users_id_editor", "int NOT NULL DEFAULT '0'", ['after' => 'users_id']);
        $migration->addKey("sanam_problemtasks", "users_id_editor");
    }

    //Add a sync_field in LDAP configuration
    if (!$DB->fieldExists('sanam_authldaps', 'sync_field')) {
        $migration->addField("sanam_authldaps", "sync_field", "string", ['after' => 'login_field', 'null' => true]);
        $migration->addKey('sanam_authldaps', 'sync_field');
    }
    //Add a sync_field for users
    if (!$DB->fieldExists('sanam_users', 'sync_field')) {
        $migration->addField("sanam_users", "sync_field", "string", ['null' => true]);
        $migration->addKey('sanam_users', 'sync_field');
    }

    $migration->addConfig([
        'smtp_max_retries'   => 5,
        'smtp_sender'        => 'NULL',
        'from_email'         => 'NULL',
        'from_email_name'    => 'NULL',
    ]);

    //register telemetry crontask
    $migration->addCrontask(
        'Telemetry',
        'telemetry',
        MONTH_TIMESTAMP,
        options: [
            'state' => 0, // CronTask::STATE_DISABLE
            'mode'  => 1, // CronTask::MODE_INTERNAL
        ]
    );
    $migration->addConfig([
        'instance_uuid'      => Toolbox::getRandomString(40),
        'registration_uuid'  => Toolbox::getRandomString(40),
    ]);

    if (isIndex('sanam_authldaps', 'use_tls')) {
        $query = "ALTER TABLE `sanam_authldaps` DROP INDEX `use_tls`";
        $DB->doQuery($query);
    }

    //Fix some field order from old migrations
    $migration->migrationOneTable('sanam_states');
    $DB->doQuery("ALTER TABLE `sanam_budgets` CHANGE `date_creation` `date_creation` DATETIME NULL DEFAULT NULL AFTER `date_mod`");
    $DB->doQuery("ALTER TABLE `sanam_changetasks` CHANGE `groups_id_tech` `groups_id_tech` INT NOT NULL DEFAULT '0' AFTER `users_id_tech`");
    $DB->doQuery("ALTER TABLE `sanam_problemtasks` CHANGE `groups_id_tech` `groups_id_tech` INT NOT NULL DEFAULT '0' AFTER `users_id_tech`");
    $DB->doQuery("ALTER TABLE `sanam_tickettasks` CHANGE `groups_id_tech` `groups_id_tech` INT NOT NULL DEFAULT '0' AFTER `users_id_tech`");
    $DB->doQuery("ALTER TABLE `sanam_knowbaseitemcategories` CHANGE `sons_cache` `sons_cache` LONGTEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `level`");
    $DB->doQuery("ALTER TABLE `sanam_requesttypes` CHANGE `is_followup_default` `is_followup_default` TINYINT NOT NULL DEFAULT '0' AFTER `is_helpdesk_default`");
    $DB->doQuery("ALTER TABLE `sanam_requesttypes` CHANGE `is_mailfollowup_default` `is_mailfollowup_default` TINYINT NOT NULL DEFAULT '0' AFTER `is_mail_default`");
    $DB->doQuery("ALTER TABLE `sanam_requesttypes` CHANGE `comment` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `is_ticketfollowup`");
    $DB->doQuery("ALTER TABLE `sanam_requesttypes` CHANGE `date_mod` `date_mod` DATETIME NULL DEFAULT NULL AFTER `comment`");
    $DB->doQuery("ALTER TABLE `sanam_requesttypes` CHANGE `date_creation` `date_creation` DATETIME NULL DEFAULT NULL AFTER `date_mod`");
    $DB->doQuery("ALTER TABLE `sanam_groups` CHANGE `is_task` `is_task` TINYINT NOT NULL DEFAULT '1' AFTER `is_assign`");
    $DB->doQuery("ALTER TABLE `sanam_states` CHANGE `date_mod` `date_mod` DATETIME NULL DEFAULT NULL AFTER `is_visible_certificate`");
    $DB->doQuery("ALTER TABLE `sanam_states` CHANGE `date_creation` `date_creation` DATETIME NULL DEFAULT NULL AFTER `date_mod`");
    $DB->doQuery("ALTER TABLE `sanam_taskcategories` CHANGE `is_active` `is_active` TINYINT NOT NULL DEFAULT '1' AFTER `sons_cache`");
    $DB->doQuery("ALTER TABLE `sanam_users` CHANGE `palette` `palette` CHAR(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `layout`");
    $DB->doQuery("ALTER TABLE `sanam_users` CHANGE `set_default_requester` `set_default_requester` TINYINT NULL DEFAULT NULL AFTER `ticket_timeline_keep_replaced_tabs`");
    $DB->doQuery("ALTER TABLE `sanam_users` CHANGE `plannings` `plannings` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `highcontrast_css`");

    //Fix bad default values
    $DB->doQuery("ALTER TABLE `sanam_states` CHANGE `is_visible_softwarelicense` `is_visible_softwarelicense` TINYINT NOT NULL DEFAULT '1'");
    $DB->doQuery("ALTER TABLE `sanam_states` CHANGE `is_visible_line` `is_visible_line` TINYINT NOT NULL DEFAULT '1'");

    //Fields added in 0905_91 script but not in empty sql...
    if (!$DB->fieldExists('sanam_changetasks', 'date_creation', false)) {
        $migration->addField('sanam_changetasks', 'date_creation', 'datetime', ['after' => 'date_mod']);
        $migration->addKey('sanam_changetasks', 'date_creation');
    }
    if (!$DB->fieldExists('sanam_networkportfiberchannels', 'date_mod', false)) {
        $migration->addField('sanam_networkportfiberchannels', 'date_mod', 'datetime', ['after' => 'speed']);
        $migration->addKey('sanam_networkportfiberchannels', 'date_mod');
    }
    if (!$DB->fieldExists('sanam_networkportfiberchannels', 'date_creation', false)) {
        $migration->addField('sanam_networkportfiberchannels', 'date_creation', 'datetime', ['after' => 'date_mod']);
        $migration->addKey('sanam_networkportfiberchannels', 'date_creation');
    }
    if (!$DB->fieldExists('sanam_problemtasks', 'date_creation', false)) {
        $migration->addField('sanam_problemtasks', 'date_creation', 'datetime', ['after' => 'date_mod']);
        $migration->addKey('sanam_problemtasks', 'date_creation');
    }
    if (!$DB->fieldExists('sanam_slms', 'date_creation', false)) {
        $migration->addField('sanam_slms', 'date_creation', 'datetime', ['after' => 'date_mod']);
        $migration->addKey('sanam_slms', 'date_creation');
    }
    if (!$DB->fieldExists('sanam_ticketfollowups', 'date_creation', false)) {
        $migration->addField('sanam_ticketfollowups', 'date_creation', 'datetime', ['after' => 'date_mod']);
        $migration->addKey('sanam_ticketfollowups', 'date_creation');
    }
    if (!$DB->fieldExists('sanam_tickettasks', 'date_creation', false)) {
        $migration->addField('sanam_tickettasks', 'date_creation', 'datetime', ['after' => 'date_mod']);
        $migration->addKey('sanam_tickettasks', 'date_creation');
    }
    if (!$DB->fieldExists('sanam_softwarelicenses', 'contact_num', false)) {
        $migration->addField(
            "sanam_softwarelicenses",
            "contact_num",
            "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL",
            ['after' => 'contact']
        );
    }

    //Fix comments...
    $DB->doQuery("ALTER TABLE `sanam_savedsearches` CHANGE `type` `type` INT NOT NULL DEFAULT '0' COMMENT 'see SavedSearch:: constants'");

    //Fix unicity...
    $tables = [
        'sanam_slalevels_tickets'   => ['tickets_id', 'slalevels_id'],
        'sanam_businesscriticities' => ['businesscriticities_id', 'name'],
        'sanam_documentcategories'  => ['documentcategories_id', 'name'],
        'sanam_olalevels_tickets'   => ['tickets_id', 'olalevels_id'],
        'sanam_states'              => ['states_id', 'name'],
        'sanam_tickets_tickets'     => ['tickets_id_1', 'tickets_id_2'],
        'sanam_tickettemplatehiddenfields'      => ['tickettemplates_id', 'num'],
        'sanam_tickettemplatemandatoryfields'   => ['tickettemplates_id', 'num'],
    ];
    foreach ($tables as $table => $fields) {
        $add = true;
        $result = $DB->doQuery("SHOW INDEX FROM `$table` WHERE Key_name='unicity'");
        if ($result && $DB->numrows($result)) {
            $row = $DB->fetchAssoc($result);
            if ($row['Non_unique'] == 1) {
                $migration->dropKey($table, 'unicity');
                $migration->migrationOneTable($table);
            } else {
                $add = false;
            }
        }

        if ($add) {
            //missing or bad unique key ==> add it.
            $migration->addKey(
                $table,
                $fields,
                'unicity',
                'UNIQUE'
            );
        }
    }

    // unique index added in a previous 9.2-dev
    $migration->dropKey('sanam_tickettemplatepredefinedfields', 'unicity');
    $migration->addKey(
        'sanam_tickettemplatepredefinedfields',
        ['tickettemplates_id', 'num'],
        'tickettemplates_id_id_num'
    );

    //removed field
    if ($DB->fieldExists('sanam_slms', 'resolution_time')) {
        $migration->dropField('sanam_slms', 'resolution_time');
    }

    //wrong type
    $DB->doQuery("ALTER TABLE `sanam_users` CHANGE `keep_devices_when_purging_item` `keep_devices_when_purging_item` TINYINT NULL DEFAULT NULL");

    //missing index
    $migration->addKey('sanam_networknames', 'is_deleted');
    $migration->addKey('sanam_networknames', 'is_dynamic');
    $migration->addKey('sanam_projects', 'is_template');
    $migration->addKey('sanam_projecttasks', 'is_template');
    $migration->dropKey('sanam_savedsearches_users', 'bookmarks_id');
    $migration->addKey('sanam_savedsearches_users', 'savedsearches_id');
    //this one was not on the correct field
    $migration->dropKey('sanam_softwarelicenses', 'is_deleted');
    $migration->migrationOneTable('sanam_softwarelicenses');
    $migration->addKey('sanam_softwarelicenses', 'is_deleted');
    $migration->addKey('sanam_states', 'is_visible_line');
    $migration->addKey('sanam_tasktemplates', 'is_private');
    $migration->addKey('sanam_tasktemplates', 'users_id_tech');
    $migration->addKey('sanam_tasktemplates', 'groups_id_tech');

    //add timeline_position in ticketfollowups
    //add timeline_position in tickettasks
    //add timeline_position in documents_items
    //add timeline_position in ticketvalidations
    $timeline_tables = ['sanam_ticketfollowups', 'sanam_tickettasks', 'sanam_documents_items', 'sanam_ticketvalidations'];
    foreach ($timeline_tables as $tl_table) {
        //add timeline_position in $tl_table
        if (!$DB->fieldExists($tl_table, 'timeline_position')) {
            $migration->addField($tl_table, "timeline_position", "tinyint NOT NULL DEFAULT '0'");
            $where = [
                "$tl_table.tickets_id"  => new QueryExpression(
                    DBmysql::quoteName("sanam_tickets_users.tickets_id")
                ),
                "$tl_table.users_id"    => new QueryExpression(
                    DBmysql::quoteName("sanam_tickets_users.users_id")
                ),
            ];
            if (!$DB->fieldExists($tl_table, 'tickets_id')) {
                $where = [
                    "$tl_table.itemtype"    => "Ticket",
                    "$tl_table.items_id"    => new QueryExpression(
                        DBmysql::quoteName("sanam_tickets_users.tickets_id")
                    ),
                    "$tl_table.users_id"    => new QueryExpression(
                        DBmysql::quoteName("sanam_tickets_users.users_id")
                    ),
                ];
            }

            $update = new QueryExpression(
                DBmysql::quoteName($tl_table) . ", " . DBmysql::quoteName("sanam_tickets_users")
            );
            $migration->addPostQuery(
                $DB->buildUpdate(
                    $update,
                    [
                        "$tl_table.timeline_position" => new QueryExpression("IF("
                     . DBmysql::quoteName("sanam_tickets_users.type") . " NOT IN (1,3) AND "
                     . DBmysql::quoteName("sanam_tickets_users.type") . " IN (2), 4, 1)"),
                    ],
                    $where
                )
            );

            $where = [
                "$tl_table.tickets_id"           => new QueryExpression(
                    DBmysql::quoteName("sanam_groups_tickets.tickets_id")
                ),
                "sanam_groups_users.groups_id"    => new QueryExpression(
                    DBmysql::quoteName("sanam_groups_tickets.groups_id")
                ),
                "$tl_table.users_id"             => new QueryExpression(
                    DBmysql::quoteName("sanam_groups_users.users_id")
                ),
            ];
            if (!$DB->fieldExists($tl_table, 'tickets_id')) {
                $where = [
                    "$tl_table.itemtype"             => "Ticket",
                    "$tl_table.items_id"             => new QueryExpression(
                        DBmysql::quoteName("sanam_groups_tickets.tickets_id")
                    ),
                    "sanam_groups_users.groups_id"    => new QueryExpression(
                        DBmysql::quoteName("sanam_groups_tickets.groups_id")
                    ),
                    "$tl_table.users_id"             => new QueryExpression(
                        DBmysql::quoteName("sanam_groups_users.users_id")
                    ),
                ];
            }

            $update = new QueryExpression(
                DBmysql::quoteName($tl_table) . ", " . DBmysql::quoteName("sanam_groups_tickets")
                . ", " . DBmysql::quoteName("sanam_groups_users")
            );
            $migration->addPostQuery(
                $DB->buildUpdate(
                    $update,
                    [
                        "$tl_table.timeline_position" => new QueryExpression("IF("
                     . DBmysql::quoteName("sanam_groups_tickets.type") . " NOT IN (1,3) AND "
                     . DBmysql::quoteName("sanam_groups_tickets.type") . " IN (2), 4, 1)"),
                    ],
                    $where
                )
            );

            $migration->addPostQuery(
                $DB->buildUpdate(
                    $tl_table,
                    ["$tl_table.timeline_position" => 1],
                    ["$tl_table.timeline_position" => 0]
                )
            );
        }
    }

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
