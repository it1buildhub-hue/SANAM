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
 * Update from 9.1.1 to 9.1.3
 *
 * @return bool
 **/
function update911to913()
{
    /**
     * @var DBmysql $DB
     * @var Migration $migration
     */
    global $DB, $migration;

    $updateresult     = true;

    $migration->setVersion('9.1.3');

    //Fix duplicated search options
    if (countElementsInTable("sanam_displaypreferences", ['itemtype' => 'IPNetwork', 'num' => '17']) == 0) {
        $DB->update(
            "sanam_displaypreferences",
            [
                "num" => 17,
            ],
            [
                'itemtype'  => "IPNetwork",
                'num'       => 13,
            ]
        );
    }
    if (countElementsInTable("sanam_displaypreferences", ['itemtype' => 'IPNetwork', 'num' => '18']) == 0) {
        $DB->update(
            "sanam_displaypreferences",
            [
                "num" => 18,
            ],
            [
                'itemtype'  => "IPNetwork",
                'num'       => 14,
            ]
        );
    }

    $migration->addField(
        "sanam_softwarelicenses",
        "contact",
        "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL"
    );
    $migration->addField(
        "sanam_softwarelicenses",
        "contact_num",
        "varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL"
    );

    // ************ Keep it at the end **************
    $migration->executeMigration();

    return $updateresult;
}
