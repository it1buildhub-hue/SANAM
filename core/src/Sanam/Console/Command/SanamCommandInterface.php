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

namespace Sanam\Console\Command;

use Sanam\System\Requirement\RequirementInterface;

interface SanamCommandInterface
{
    /**
     * Defines whether or not mandatory requirements must be checked before running command.
     *
     * @return bool
     */
    public function mustCheckMandatoryRequirements(): bool;

    /**
     * Defines whether or not mandatory requirements must be checked before running command.
     *
     * @return RequirementInterface[]
     */
    public function getSpecificMandatoryRequirements(): array;

    /**
     * Defines whether or not command requires an up-to-date database to be executed.
     *
     * @return bool
     */
    public function requiresUpToDateDb(): bool;
}
