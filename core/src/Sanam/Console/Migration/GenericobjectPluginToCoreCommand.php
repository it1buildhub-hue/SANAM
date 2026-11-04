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

namespace Sanam\Console\Migration;

use Sanam\Migration\AbstractPluginMigration;
use Sanam\Migration\GenericobjectPluginMigration;
use Override;

class GenericobjectPluginToCoreCommand extends AbstractPluginMigrationCommand
{
    #[Override]
    public function getName(): string
    {
        return 'migration:genericobject_plugin_to_core';
    }

    #[Override]
    public function getDescription(): string
    {
        return sprintf(__('Migrate plugin data into SANAM core tables'), 'GenericObject');
    }

    #[Override]
    public function getMigration(): AbstractPluginMigration
    {
        return new GenericobjectPluginMigration($this->db);
    }
}
