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
 * Update from 9.2 to 9.2.1
 *
 * @return bool
 **/
function update920to921()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;

    $migration->setVersion('9.2.1');

    //fix migration parts that may not been ran from 9.1.x update
    //see #
    // Slalevels changes => changes in 9.2 migration, impossible to fix here.

    // Ticket changes
    if ($DB->fieldExists('sanam_tickets', 'slas_id')) {
        $migration->changeField("sanam_tickets", "slas_id", "slts_ttr_id", "integer");
        $migration->migrationOneTable('sanam_tickets');
    }
    if (isIndex('sanam_tickets', 'slas_id')) {
        $migration->dropKey('sanam_tickets', 'slas_id');
    }
    if ($DB->fieldExists('sanam_tickets', 'slts_ttr_id')) {
        $migration->addKey('sanam_tickets', 'slts_ttr_id');
    }

    if (!$DB->fieldExists('sanam_tickets', 'time_to_own')) {
        $after = 'due_date';
        if (!$DB->fieldExists('sanam_tickets', 'due_date')) {
            $after = 'time_to_resolve';
        }
        $migration->addField("sanam_tickets", "time_to_own", "datetime", ['after' => $after]);
        $migration->addKey('sanam_tickets', 'time_to_own');
    }

    if ($DB->fieldExists('sanam_tickets', 'slalevels_id')) {
        $migration->changeField('sanam_tickets', 'slalevels_id', 'ttr_slalevels_id', 'integer');
        $migration->migrationOneTable('sanam_tickets');
        $migration->dropKey('sanam_tickets', 'slalevels_id');
    }
    if ($DB->fieldExists('sanam_tickets', 'ttr_slalevels_id')) {
        $migration->addKey('sanam_tickets', 'ttr_slalevels_id');
    }

    // Sla rules criterias migration
    $DB->update(
        "sanam_rulecriterias",
        ['criteria' => "slts_ttr_id"],
        ['criteria' => "slas_id"]
    );

    // Sla rules actions migration
    $DB->update(
        "sanam_ruleactions",
        ['field' => "slts_ttr_id"],
        ['field' => "slas_id"]
    );
    // end fix 9.1.x migration

    //fix migration parts that may not been ran from previous update
    //see #
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

        $DB->insert("sanam_crontasks", [
            'itemtype'        => "OlaLevel_Ticket",
            'name'            => "olaticket",
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
    //this one was missing
    $migration->addKey('sanam_tickets', 'ola_waiting_duration');

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

    //see #
    $migration->addPreQuery(
        $DB->buildUpdate(
            "sanam_crontasks",
            ['itemtype' => "QueuedNotification"],
            ['itemtype' => "QueuedMail"]
        )
    );

    $migration->addPreQuery(
        $DB->buildUpdate(
            "sanam_crontasks",
            ['name' => "queuednotification"],
            ['name' => "queuedmail"]
        )
    );

    $migration->addPreQuery(
        $DB->buildUpdate(
            "sanam_crontasks",
            ['name' => "queuednotificationclean"],
            ['name' => "queuedmailclean"]
        )
    );

    // TODO: can be done when DB::delete() supports JOINs
    $migration->addPreQuery("DELETE `duplicated` FROM `sanam_profilerights` AS `duplicated`
                            INNER JOIN `sanam_profilerights` AS `original`
                            WHERE `duplicated`.`profiles_id` = `original`.`profiles_id`
                            AND `original`.`name` = 'queuednotification'
                            AND `duplicated`.`name` = 'queuedmail'");

    $migration->addPreQuery(
        $DB->buildUpdate(
            "sanam_profilerights",
            ['name' => "queuednotification"],
            ['name' => "queuedmail"]
        )
    );

    //ensure do_count is set to AUTO
    //do_count update query may have been affected, but we cannot run it here
    $migration->addPreQuery(
        $DB->buildUpdate(
            "sanam_savedsearches",
            ['entities_id' => 0],
            ['entities_id' => -1]
        )
    );

    if ($DB->fieldExists("sanam_notifications", "mode", false)) {
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

    // add missing fields for certificates working in allassets.php
    $migration->addField("sanam_certificates", "contact", "string", ['after' => 'manufacturers_id']);
    $migration->addField("sanam_certificates", "contact_num", "string", ['after' => 'contact']);
    $migration->migrationOneTable("sanam_certificates");

    // end fix 9.2 migration

    //add MSIN to simcard component
    $migration->addField('sanam_items_devicesimcards', 'msin', 'string', ['after' => 'puk2', 'value' => '']);
    $migration->addField('sanam_items_devicesimcards', 'is_recursive', 'bool', ['after' => 'entities_id', 'value' => '0']);
    $migration->addKey('sanam_items_devicesimcards', 'is_recursive');

    $migration->addField(
        'sanam_items_operatingsystems',
        'is_recursive',
        "tinyint NOT NULL DEFAULT '0'",
        ['after' => 'entities_id']
    );
    $migration->addKey('sanam_items_operatingsystems', 'is_recursive');
    $migration->migrationOneTable('sanam_items_operatingsystems');

    //fix OS entities_id and is_recursive
    $items = [
        'Computer'           => 'sanam_computers',
        'Monitor'            => 'sanam_monitors',
        'NetworkEquipment'   => 'sanam_networkequipments',
        'Peripheral'         => 'sanam_peripherals',
        'Phone'              => 'sanam_phones',
        'Printer'            => 'sanam_printers',
    ];
    foreach ($items as $itemtype => $table) {
        // TODO: can be done when DB::update() supports JOINs
        $migration->addPostQuery(
            "UPDATE sanam_items_operatingsystems AS ios
            INNER JOIN `$table` as item ON ios.items_id = item.id AND ios.itemtype = '$itemtype'
            SET ios.entities_id = item.entities_id, ios.is_recursive = item.is_recursive
         "
        );
    }

    //drop "empty" sanam_items_operatingsystems
    $migration->addPostQuery(
        $DB->buildDelete("sanam_items_operatingsystems", [
            'operatingsystems_id'               => "0",
            'operatingsystemversions_id'        => "0",
            'operatingsystemservicepacks_id'    => "0",
            'operatingsystemarchitectures_id'   => "0",
            'operatingsystemkernelversions_id'  => "0",
            'operatingsystemeditions_id'        => "0",
            [
                'OR' => [
                    ['license_number' => null],
                    ['license_number' => ""],
                ],
            ],
            ['OR' => [
                ['license_id' => null],
                ['license_id' => ""],
            ],
            ],
        ])
    );

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
