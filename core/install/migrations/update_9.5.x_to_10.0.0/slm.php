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
 * @var DBmysql $DB
 * @var Migration $migration
 */
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// `sanam_slas.calendars_id` will not exist if SANAM has been initialized on a 9.1.x version.
// Indeed, field was not present in `sanam-empty.sql` file, but was present in 0.90.x->9.1.0 migration.
// It has been added in `sanam-empty.sql` file on SANAM 9.2.0 (see commit ebd8c6f097fd0461e4f9840221224cbb5e89a7a1).
if (!$DB->fieldExists('sanam_slas', 'calendars_id')) {
    $migration->addField('sanam_slas', 'calendars_id', "int {$default_key_sign} NOT NULL DEFAULT 0");
    $migration->addKey('sanam_slas', 'calendars_id');
}

// Replace usage of negative values in `calendars_id` fields
foreach (['sanam_slms', 'sanam_olas', 'sanam_slas'] as $table) {
    if (!$DB->fieldExists($table, 'use_ticket_calendar')) {
        $migration->addField($table, 'use_ticket_calendar', 'bool');
        $migration->addPostQuery(
            $DB->buildUpdate(
                $table,
                [
                    'use_ticket_calendar' => 1,
                    'calendars_id'        => 0,
                ],
                [
                    'calendars_id'        => -1,
                ]
            )
        );
    }
}

// Copy calendar settings from SLM to children
foreach (['sanam_olas', 'sanam_slas'] as $table) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            $table,
            [
                $table . '.use_ticket_calendar' => new QueryExpression($DB->quoteName('sanam_slms.use_ticket_calendar')),
                $table . '.calendars_id'        => new QueryExpression($DB->quoteName('sanam_slms.calendars_id')),
            ],
            [
                new QueryExpression('true'),
            ],
            [
                'INNER JOIN' => [
                    'sanam_slms' => [
                        'FKEY' => [
                            $table      => 'slms_id',
                            'sanam_slms' => 'id',
                        ],
                    ],
                ],
            ]
        )
    );
}
