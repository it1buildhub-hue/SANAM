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

use Sanam\Features\Clonable;

/**
 * Ticket Template class
 *
 * since version 0.83
 **/
class TicketTemplate extends ITILTemplate
{
    /** @use Clonable<static> */
    use Clonable;

    #[Override]
    public static function getPredefinedFields(): ITILTemplatePredefinedField
    {
        return new TicketTemplatePredefinedField();
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Ticket template', 'Ticket templates', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['helpdesk', Ticket::class, self::class];
    }

    public function getCloneRelations(): array
    {
        return [
            TicketTemplateHiddenField::class,
            TicketTemplateMandatoryField::class,
            TicketTemplatePredefinedField::class,
            TicketTemplateReadonlyField::class,
        ];
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                TicketTemplateHiddenField::class,
                TicketTemplateMandatoryField::class,
                TicketTemplatePredefinedField::class,
            ]
        );
    }

    public static function getExtraAllowedFields($withtypeandcategory = false, $withitemtype = false)
    {
        $itil_object = new Ticket();
        $tab =  [
            $itil_object->getSearchOptionIDByField(
                'field',
                'name',
                'sanam_requesttypes'
            )
                                                       => 'requesttypes_id',
            $itil_object->getSearchOptionIDByField(
                'field',
                'slas_id_tto',
                'sanam_slas'
            )      => 'slas_id_tto',
            $itil_object->getSearchOptionIDByField(
                'field',
                'slas_id_ttr',
                'sanam_slas'
            )      => 'slas_id_ttr',
            $itil_object->getSearchOptionIDByField(
                'field',
                'olas_id_tto',
                'sanam_olas'
            )      => 'olas_id_tto',
            $itil_object->getSearchOptionIDByField(
                'field',
                'olas_id_ttr',
                'sanam_olas'
            )      => 'olas_id_ttr',
            $itil_object->getSearchOptionIDByField(
                'field',
                'time_to_own',
                'sanam_tickets'
            )   => 'time_to_own',
            $itil_object->getSearchOptionIDByField(
                'field',
                'internal_time_to_resolve',
                'sanam_tickets'
            )   => 'internal_time_to_resolve',
            $itil_object->getSearchOptionIDByField(
                'field',
                'internal_time_to_own',
                'sanam_tickets'
            )   => 'internal_time_to_own',
            $itil_object->getSearchOptionIDByField(
                'field',
                'global_validation',
                'sanam_tickets'
            )   => 'global_validation',
            $itil_object->getSearchOptionIDByField(
                'field',
                'name',
                'sanam_contracts'
            )   => '_contracts_id',
            $itil_object->getSearchOptionIDByField(
                'field',
                'type',
                'sanam_tickets'
            )   => 'type',
            $itil_object->getSearchOptionIDByField(
                'field',
                'externalid',
                'sanam_tickets'
            )   => 'externalid',
        ];

        if ($withtypeandcategory) {
            $tab[$itil_object->getSearchOptionIDByField(
                'field',
                'type',
                $itil_object->getTable()
            )]         = 'type';
        }

        return $tab;
    }
}
