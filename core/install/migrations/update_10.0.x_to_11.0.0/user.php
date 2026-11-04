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
if (!$DB->fieldExists('sanam_users', 'is_notif_enable_default')) {
    $migration->addField('sanam_users', 'is_notif_enable_default', "tinyint DEFAULT NULL");
}

$migration->addConfig(['is_notif_enable_default' => 1]);

// search ux improvements (#15861)
$migration->addField('sanam_users', 'show_search_form', "tinyint DEFAULT NULL");
$migration->addField('sanam_users', 'search_pagination_on_top', "tinyint DEFAULT NULL");
$migration->dropField('sanam_users', 'fold_search');

$migration->addConfig(['show_search_form' => 0]);
$migration->addConfig(['search_pagination_on_top' => 0]);
$migration->removeConfig(['fold_search']);

// Drop useless field
$migration->dropField('sanam_users', 'display_options');

// Drop "picture" search option
$migration->removeSearchOption('User', 150);
