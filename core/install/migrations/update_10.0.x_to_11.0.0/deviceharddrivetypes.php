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
$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

$migration->addField('sanam_deviceharddrives', 'deviceharddrivetypes_id', 'fkey');
$migration->addKey('sanam_deviceharddrives', 'deviceharddrivetypes_id', "deviceharddrivetypes_id");

if (!$DB->tableExists('sanam_deviceharddrivetypes')) {
    $query = "CREATE TABLE `sanam_deviceharddrivetypes` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `name` varchar(255) DEFAULT NULL,
        `comment` text,
        PRIMARY KEY (`id`),
        KEY `name` (`name`)
      ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC";
    $DB->doQuery($query);
}
