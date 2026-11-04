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
 * Chang Template class
 *
 * @since 9.5.0
 **/
class ChangeTemplate extends ITILTemplate
{
    /** @use Clonable<static> */
    use Clonable;

    #[Override]
    public static function getPredefinedFields(): ITILTemplatePredefinedField
    {
        return new ChangeTemplatePredefinedField();
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Change template', 'Change templates', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['helpdesk', Change::class, self::class];
    }

    public function getCloneRelations(): array
    {
        return [
            ChangeTemplateHiddenField::class,
            ChangeTemplateMandatoryField::class,
            ChangeTemplatePredefinedField::class,
            ChangeTemplateReadonlyField::class,
        ];
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                ChangeTemplateHiddenField::class,
                ChangeTemplateMandatoryField::class,
                ChangeTemplatePredefinedField::class,
            ]
        );
    }

    public static function getExtraAllowedFields($withtypeandcategory = false, $withitemtype = false)
    {
        $change = new Change();
        return [
            $change->getSearchOptionIDByField('field', 'time_to_resolve', 'sanam_changes') => 'time_to_resolve',
            $change->getSearchOptionIDByField('field', 'impactcontent', 'sanam_changes')      => 'impactcontent',
            $change->getSearchOptionIDByField('field', 'controlistcontent', 'sanam_changes')  => 'controlistcontent',
            $change->getSearchOptionIDByField('field', 'rolloutplancontent', 'sanam_changes') => 'rolloutplancontent',
            $change->getSearchOptionIDByField('field', 'backoutplancontent', 'sanam_changes') => 'backoutplancontent',
            $change->getSearchOptionIDByField('field', 'checklistcontent', 'sanam_changes')   => 'checklistcontent',
        ];
    }
}
