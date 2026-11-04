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

namespace Sanam\Form\Destination\CommonITILField;

use Sanam\Form\Form;
use Sanam\Form\QuestionType\QuestionTypeAssignee;
use Sanam\Form\QuestionType\QuestionTypeEmail;
use Group;
use Override;
use Session;
use Supplier;
use User;

final class AssigneeField extends ITILActorField
{
    #[Override]
    public function getAllowedQuestionType(): array
    {
        return [new QuestionTypeAssignee(), new QuestionTypeEmail()];
    }

    #[Override]
    public function getAllowedQuestionItemTypes(): array
    {
        return [User::class, Group::class, Supplier::class];
    }

    #[Override]
    public function getActorType(): string
    {
        return 'assign';
    }

    #[Override]
    public function getLabel(): string
    {
        return _n('Assignee', 'Assignees', Session::getPluralNumber());
    }

    #[Override]
    public function getWeight(): int
    {
        return 120;
    }

    #[Override]
    public function getConfigClass(): string
    {
        return AssigneeFieldConfig::class;
    }

    #[Override]
    public function getDefaultConfig(Form $form): AssigneeFieldConfig
    {
        return new AssigneeFieldConfig(
            [ITILActorFieldStrategy::FROM_TEMPLATE],
        );
    }
}
