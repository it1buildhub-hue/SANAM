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

$assets_tables_with_templates_and_trashbin = [
    'sanam_budgets',
    'sanam_certificates',
    'sanam_computers',
    'sanam_contracts',
    'sanam_domains',
    'sanam_cables',
    'sanam_monitors',
    'sanam_networkequipments',
    'sanam_passivedcequipments',
    'sanam_peripherals',
    'sanam_phones',
    'sanam_printers',
    'sanam_projects',
    'sanam_projecttasks',
    'sanam_softwarelicenses',
    'sanam_softwares',
    'sanam_racks',
    'sanam_enclosures',
    'sanam_pdus',
    'sanam_assets_assets',
];

foreach ($assets_tables_with_templates_and_trashbin as $table) {
    $migration->addKey($table, ['is_deleted', 'is_template'], 'active_assets');
    $migration->dropKey($table, 'is_deleted');
}
