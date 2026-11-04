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
 * Update from 9.3 to 9.4
 *
 * @return bool
 **/
function update93xto940()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;
    $ADDTODISPLAYPREF = [];
    $config_to_drop = [];

    $migration->setVersion('9.4.0');

    /** Add otherserial field on ConsumableItem */
    if (!$DB->fieldExists('sanam_consumableitems', 'otherserial')) {
        $migration->addField("sanam_consumableitems", "otherserial", "varchar(255) NULL DEFAULT NULL");
        $migration->addKey("sanam_consumableitems", 'otherserial');
    }
    /** /Add otherserial field on ConsumableItem */

    /** Add default group for a user */
    if ($migration->addField('sanam_users', 'groups_id', 'integer')) {
        $migration->addKey('sanam_users', 'groups_id');
    }
    /** /Add default group for a user */

    /** Add requester field on sanam_mailcollectors */
    $migration->addField("sanam_mailcollectors", "requester_field", "integer", [
        'value' => '0',
    ]);
    /** /Add requester field on sanam_mailcollectors */

    /** Increase value length for criteria */
    $migration->changeField('sanam_rulecriterias', 'pattern', 'pattern', 'text');
    /** /Increase value length for criteria */

    /** Add business rules on assets */
    $rule = ['name'         => 'Domain user assignation',
        'is_active'    => 1,
        'is_recursive' => 1,
        'sub_type'     => 'RuleAsset',
        'condition'    => 3,
        'entities_id'  => 0,
        'uuid'         => 'fbeb1115-7a37b143-5a3a6fc1afdc17.92779763',
        'match'        => Rule::AND_MATCHING,
    ];
    $criteria = [
        ['criteria' => '_itemtype', 'condition' => Rule::PATTERN_IS, 'pattern' => 'Computer'],
        ['criteria' => '_auto', 'condition' => Rule::PATTERN_IS, 'pattern' => 1],
        ['criteria' => 'contact', 'condition' => Rule::REGEX_MATCH, 'pattern' => '/(.*)@/'],
    ];
    $action = [['action_type' => 'regex_result', 'field' => '_affect_user_by_regex', 'value' => '#0']];
    $migration->createRule($rule, $criteria, $action);

    $rule = ['name'         => 'Multiple users: assign to the first',
        'is_active'    => 1,
        'is_recursive' => 1,
        'sub_type'     => 'RuleAsset',
        'condition'    => 3,
        'entities_id'  => 0,
        'uuid'         => 'fbeb1115-7a37b143-5a3a6fc1b03762.88595154',
        'match'        => Rule::AND_MATCHING,
    ];
    $criteria = [
        ['criteria' => '_itemtype', 'condition' => Rule::PATTERN_IS, 'pattern' => 'Computer'],
        ['criteria' => '_auto', 'condition' => Rule::PATTERN_IS, 'pattern' => 1],
        ['criteria' => 'contact', 'condition' => Rule::REGEX_MATCH, 'pattern' => '/(.*),/'],
    ];
    $migration->createRule($rule, $criteria, $action);

    $rule = ['name'         => 'One user assignation',
        'is_active'    => 1,
        'is_recursive' => 1,
        'sub_type'     => 'RuleAsset',
        'condition'    => 3,
        'entities_id'  => 0,
        'uuid'         => 'fbeb1115-7a37b143-5a3a6fc1b073e1.16257440',
        'match'        => Rule::AND_MATCHING,
    ];
    $criteria = [
        ['criteria' => '_itemtype', 'condition' => Rule::PATTERN_IS, 'pattern' => 'Computer'],
        ['criteria' => '_auto', 'condition' => Rule::PATTERN_IS, 'pattern' => 1],
        ['criteria' => 'contact', 'condition' => Rule::REGEX_MATCH, 'pattern' => '/(.*)/'],
    ];
    $migration->createRule($rule, $criteria, $action);

    if (!countElementsInTable('sanam_profilerights', ['profiles_id' => 4, 'name' => 'rule_asset'])) {
        $DB->insert("sanam_profilerights", [
            'id'           => null,
            'profiles_id'  => "4",
            'name'         => "rule_asset",
            'rights'       => "255",
        ]);
    }
    /** /Add business rules on assets */

    /** Drop use_rich_text parameter */
    $config_to_drop[] = 'use_rich_text';
    /** /Drop use_rich_text parameter */

    /** Drop ticket_timeline* parameters */
    $config_to_drop[] = 'ticket_timeline';
    $config_to_drop[] = 'ticket_timeline_keep_replaced_tabs';
    $migration->dropField('sanam_users', 'ticket_timeline');
    $migration->dropField('sanam_users', 'ticket_timeline_keep_replaced_tabs');
    /** /Drop ticket_timeline* parameters */

    /** Replacing changes_projects by itils_projects */
    if ($DB->tableExists('sanam_changes_projects')) {
        $migration->renameTable('sanam_changes_projects', 'sanam_itils_projects');

        $migration->dropKey('sanam_itils_projects', 'unicity');
        // Key have to be dropped now to be able to create a new one having same name
        $migration->migrationOneTable('sanam_itils_projects');

        $migration->addField(
            'sanam_itils_projects',
            'itemtype',
            "varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''",
            [
                'after'  => 'id',
                'update' => "'Change'",
            ]
        );

        $migration->changeField(
            'sanam_itils_projects',
            'changes_id',
            'items_id',
            "int NOT NULL DEFAULT '0'"
        );

        $migration->addKey(
            'sanam_itils_projects',
            ['itemtype', 'items_id', 'projects_id'],
            'unicity',
            'UNIQUE'
        );
        $migration->migrationOneTable('sanam_itils_projects');
    }
    /** /Replacing changes_projects by itils_projects */

    /** Rename non fkey field */
    $migration->changeField(
        'sanam_items_operatingsystems',
        'license_id',
        'licenseid',
        "string"
    );
    /** Rename non fkey field */

    /** Add watcher visibility to groups */
    if (!$DB->fieldExists('sanam_groups', 'is_watcher')) {
        if ($migration->addField('sanam_groups', 'is_watcher', "tinyint NOT NULL DEFAULT '1'", ['after' => 'is_requester'])) {
            $migration->addKey('sanam_groups', 'is_watcher');
            $migration->migrationOneTable('sanam_groups');
        }
    }
    /** Add watcher visibility to groups */

    $migration->removeConfig($config_to_drop);

    // Add a config entry for the CAS version
    $migration->addConfig(['cas_version' => 'CAS_VERSION_2_0']);

    /** Drop old embed ocs search options */
    $DB->delete(
        'sanam_displaypreferences',
        [
            'itemtype'  => 'Computer',
            'num'       => [
                100,
                101,
                102,
                103,
                104,
                105,
                106,
                110,
                111,
            ],
        ]
    );
    /** /Drop old embed ocs search options */

    /** Factorize components search options on Computers, Printers and NetworkEquipments */
    $so_maping = [
        '10'  => '110',
        '35'  => '111',
        '11'  => '112',
        '20'  => '113',
        '15'  => '114',
        '34'  => '115',
        '39'  => '116',
        '95'  => '117',
    ];
    foreach ($so_maping as $old => $new) {
        $DB->update(
            'sanam_displaypreferences',
            [
                'num' => $new,
            ],
            [
                'num'       => $old,
                'itemtype'  => 'Computer',
            ]
        );
    }
    /** /Factorize components search options on Computers, Printers and NetworkEquipments */

    /** Add followup tables for new ITILFollowup class */
    if (!$DB->tableExists('sanam_itilfollowups')) {
        //Migrate ticket followups
        $migration->renameTable('sanam_ticketfollowups', 'sanam_itilfollowups');
        $migration->addField(
            'sanam_itilfollowups',
            'itemtype',
            "varchar(100) COLLATE utf8_unicode_ci NOT NULL",
            [
                'after'  => 'id',
                'update' => "'Ticket'", // Defines value for all existing elements
            ]
        );

        $migration->changeField(
            'sanam_itilfollowups',
            'tickets_id',
            'items_id',
            "int NOT NULL DEFAULT '0'"
        );
        $migration->addKey(
            'sanam_itilfollowups',
            'itemtype'
        );
        $migration->dropKey(
            'sanam_itilfollowups',
            'tickets_id'
        );
        $migration->addKey(
            'sanam_itilfollowups',
            'items_id',
            'item_id'
        );
        $migration->addKey(
            'sanam_itilfollowups',
            ['itemtype','items_id'],
            'item'
        );
    }

    if ($DB->fieldExists('sanam_requesttypes', 'is_ticketfollowup')) {
        $migration->changeField(
            'sanam_requesttypes',
            'is_ticketfollowup',
            'is_itilfollowup',
            'bool',
            ['value' => '1']
        );
        $migration->dropKey(
            'sanam_requesttypes',
            'is_ticketfollowup'
        );
        $migration->addKey(
            'sanam_requesttypes',
            'is_itilfollowup'
        );
    }

    if ($DB->fieldExists('sanam_itilsolutions', 'ticketfollowups_id')) {
        $migration->changeField(
            'sanam_itilsolutions',
            'ticketfollowups_id',
            'itilfollowups_id',
            "int DEFAULT NULL"
        );
        $migration->dropKey(
            'sanam_itilsolutions',
            'ticketfollowups_id'
        );
        $migration->addKey(
            'sanam_itilsolutions',
            'itilfollowups_id'
        );
    }

    /** Add timeline_position to Change and Problem items */
    $migration->addField("sanam_changetasks", "timeline_position", "tinyint NOT NULL DEFAULT '0'");
    $migration->addField("sanam_changevalidations", "timeline_position", "tinyint NOT NULL DEFAULT '0'");
    $migration->addField("sanam_problemtasks", "timeline_position", "tinyint NOT NULL DEFAULT '0'");

    /** Give all existing profiles access to personalizations for legacy functionality */
    $migration->addRight('personalization', READ | UPDATE, []);

    /** Search engine on plugins */
    $ADDTODISPLAYPREF['Plugin'] = [2, 3, 4, 5, 6, 7, 8];

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

    /** Renaming olas / slas foreign keys that does not match naming conventions */
    $olas_slas_mapping = [
        'olas_tto_id'      => 'olas_id_tto',
        'olas_ttr_id'      => 'olas_id_ttr',
        'ttr_olalevels_id' => 'olalevels_id_ttr',
        'slas_tto_id'      => 'slas_id_tto',
        'slas_ttr_id'      => 'slas_id_ttr',
        'ttr_slalevels_id' => 'slalevels_id_ttr',
    ];
    foreach ($olas_slas_mapping as $old_fieldname => $new_fieldname) {
        if ($DB->fieldExists('sanam_tickets', $old_fieldname)) {
            $migration->changeField('sanam_tickets', $old_fieldname, $new_fieldname, 'integer');
        }
        $migration->dropKey('sanam_tickets', $old_fieldname);
        $migration->addKey('sanam_tickets', $new_fieldname);

        $migration->addPostQuery(
            $DB->buildUpdate(
                'sanam_rulecriterias',
                [
                    'criteria' => $new_fieldname,
                ],
                [
                    'criteria' => $old_fieldname,
                ]
            )
        );

        $migration->addPostQuery(
            $DB->buildUpdate(
                'sanam_ruleactions',
                [
                    'field' => $new_fieldname,
                ],
                [
                    'field' => $old_fieldname,
                ]
            )
        );
    }

    /** Adding the responsible field */
    if (!$DB->fieldExists('sanam_users', 'users_id_supervisor')) {
        if ($migration->addField('sanam_users', 'users_id_supervisor', 'integer')) {
            $migration->addKey('sanam_users', 'users_id_supervisor');
        }
        $migration->addField(
            'sanam_authldaps',
            'responsible_field',
            "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL",
            [
                'after'  => 'location_field',
            ]
        );
    }

    /** Add source item id to ITILFollowups. Used by followups created by merging tickets */
    if (!$DB->fieldExists('sanam_itilfollowups', 'sourceitems_id')) {
        if ($migration->addField('sanam_itilfollowups', 'sourceitems_id', "int NOT NULL DEFAULT '0'")) {
            $migration->addKey('sanam_itilfollowups', 'sourceitems_id');
        }
    }

    /** Add sourceof item id to ITILFollowups. Used to link to tickets created by promotion */
    if (!$DB->fieldExists('sanam_itilfollowups', 'sourceof_items_id')) {
        if ($migration->addField('sanam_itilfollowups', 'sourceof_items_id', "int NOT NULL DEFAULT '0'")) {
            $migration->addKey('sanam_itilfollowups', 'sourceof_items_id');
        }
    }

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
