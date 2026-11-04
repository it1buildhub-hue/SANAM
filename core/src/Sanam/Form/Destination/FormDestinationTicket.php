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

namespace Sanam\Form\Destination;

use CommonITILObject;
use Sanam\Form\Destination\CommonITILField\OLATTOField;
use Sanam\Form\Destination\CommonITILField\OLATTRField;
use Sanam\Form\Destination\CommonITILField\RequestTypeField;
use Sanam\Form\Destination\CommonITILField\SLATTOField;
use Sanam\Form\Destination\CommonITILField\SLATTRField;
use Sanam\Form\Destination\CommonITILField\StatusField;
use Override;
use Ticket;

final class FormDestinationTicket extends AbstractCommonITILFormDestination
{
    #[Override]
    public function getTarget(): CommonITILObject
    {
        return new Ticket();
    }

    #[Override]
    protected function defineConfigurableFields(): array
    {
        return array_merge(parent::defineConfigurableFields(), [
            new RequestTypeField(),
            new SLATTOField(),
            new SLATTRField(),
            new OLATTOField(),
            new OLATTRField(),
            new StatusField(),
        ]);
    }

    #[Override]
    public function getWeight(): int
    {
        return 10;
    }
}
