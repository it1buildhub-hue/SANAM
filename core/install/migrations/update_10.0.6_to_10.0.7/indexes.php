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
 * @var Migration $migration
 */
// Add index on level on all TreeDropdown tables
$tables = [
    'sanam_businesscriticities',
    'sanam_documentcategories',
    'sanam_entities',
    'sanam_groups',
    'sanam_ipnetworks',
    'sanam_itilcategories',
    'sanam_knowbaseitemcategories',
    'sanam_locations',
    'sanam_softwarecategories',
    'sanam_softwarelicenses',
    'sanam_softwarelicensetypes',
    'sanam_states',
    'sanam_taskcategories',
];

foreach ($tables as $table) {
    $migration->addKey($table, 'level');
}
