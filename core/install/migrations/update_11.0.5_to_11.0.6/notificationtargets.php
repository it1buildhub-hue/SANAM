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

// Remove duplicates targets
$duplicates_targets_iterator = $DB->request([
    'SELECT' => [
        'notifications_id',
        'items_id',
        'type',
        'MIN' => 'id AS min_id',
        'COUNT' => '* AS count',
    ],
    'FROM' => 'sanam_notificationtargets',
    'GROUPBY' => ['notifications_id', 'items_id', 'type'],
    'HAVING' => ['count' => ['>', 1]],
]);

foreach ($duplicates_targets_iterator as $target) {
    $DB->delete('sanam_notificationtargets', [
        'notifications_id' => $target['notifications_id'],
        'items_id' => $target['items_id'],
        'type' => $target['type'],
        'id' => ['>', $target['min_id']],
    ]);
}

$migration->dropKey('sanam_notificationtargets', 'notifications_id');
$migration->addKey('sanam_notificationtargets', ['notifications_id', 'items_id', 'type'], 'unicity', 'UNIQUE');
