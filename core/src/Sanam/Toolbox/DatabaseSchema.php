<?php

/**
 * ---------------------------------------------------------------------
 *
 * SANAM
 *
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Sanam\Toolbox;

use function Safe\realpath;

class DatabaseSchema
{
    /**
     * Return empty schema file path for given version.
     *
     * @param string $version
     *
     * @return null|string
     */
    public static function getEmptySchemaPath(string $version): ?string
    {
        $normalized_version = VersionParser::getNormalizedVersion($version, false);
        $latest_version     = VersionParser::getNormalizedVersion(SANAM_VERSION, false);

        $schema_path = $normalized_version === $latest_version
            ? sprintf('%s/install/mysql/sanam-empty.sql', realpath(SANAM_ROOT))
            : sprintf('%s/install/mysql/sanam-%s-empty.sql', realpath(SANAM_ROOT), $normalized_version);

        return file_exists($schema_path) ? $schema_path : null;
    }
}
