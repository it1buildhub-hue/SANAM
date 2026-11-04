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

namespace Sanam\System;

/**
 * @since 9.5.4
 */
class Variables
{
    /**
     * Returns list of constants corresponding to directories that contains custom data.
     *
     * @return string[]
     */
    public static function getDataDirectoriesConstants(): array
    {
        return [
            'SANAM_CACHE_DIR',
            'SANAM_CRON_DIR',
            'SANAM_DOC_DIR',
            'SANAM_GRAPH_DIR',
            'SANAM_LOCK_DIR',
            'SANAM_LOG_DIR',
            'SANAM_PICTURE_DIR',
            'SANAM_PLUGIN_DOC_DIR',
            'SANAM_RSS_DIR',
            'SANAM_SESSION_DIR',
            'SANAM_TMP_DIR',
            'SANAM_UPLOAD_DIR',
        ];
    }

    /**
     * Returns list of directories that contains custom data.
     *
     * @return string[]
     */
    public static function getDataDirectories()
    {
        return array_map(
            fn(string $constant) => constant($constant),
            self::getDataDirectoriesConstants()
        );
    }
}
