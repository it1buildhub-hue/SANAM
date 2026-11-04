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
use Sanam\DBAL\QuerySubQuery;

/**
 * Update from 9.5.5 to 9.5.6
 *
 * @return bool
 **/
function update955to956()
{
    /**
     * @var array $CFG_SANAM
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration, $CFG_SANAM;

    $updateresult     = true;

    $migration->setVersion('9.5.6');

    // Change DC itemtype template_name search option ID from 50 to 61 to prevent duplicate IDs now that those itemtypes have Infocom search options.
    $migration->changeSearchOption(Enclosure::class, 50, 61);
    $migration->changeSearchOption(PassiveDCEquipment::class, 50, 61);
    $migration->changeSearchOption(PDU::class, 50, 61);
    $migration->changeSearchOption(Rack::class, 50, 61);

    /* Add `date` to some sanam_documents_items */
    if (!$DB->fieldExists('sanam_documents_items', 'date')) {
        $migration->addField('sanam_documents_items', 'date', 'timestamp');
        $migration->addKey('sanam_documents_items', 'date');

        // Init date from the parent followup
        $parent_date = new QuerySubQuery([
            'SELECT' => 'date',
            'FROM' => 'sanam_itilfollowups',
            'WHERE' => [
                'id' => new QueryExpression($DB->quoteName('sanam_documents_items.items_id')),
            ],
        ]);

        $migration->addPostQuery(
            $DB->buildUpdate(
                'sanam_documents_items',
                ['date' => new QueryExpression($parent_date->getQuery())],
                ['itemtype' => ['ITILFollowup']]
            )
        );

        // Init date as the value of date_creation for others items
        $migration->addPostQuery(
            $DB->buildUpdate(
                'sanam_documents_items',
                ['date' => new QueryExpression($DB->quoteName('sanam_documents_items.date_creation'))],
                ['itemtype' => ['!=', 'ITILFollowup']]
            )
        );
    }
    /* /Add `date` to sanam_documents_items */

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
