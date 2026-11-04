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

namespace Sanam\Form\Migration;

use Sanam\Form\QuestionType\QuestionTypeCheckbox;
use Sanam\Form\QuestionType\QuestionTypeDateTime;
use Sanam\Form\QuestionType\QuestionTypeDropdown;
use Sanam\Form\QuestionType\QuestionTypeEmail;
use Sanam\Form\QuestionType\QuestionTypeFile;
use Sanam\Form\QuestionType\QuestionTypeItem;
use Sanam\Form\QuestionType\QuestionTypeItemDropdown;
use Sanam\Form\QuestionType\QuestionTypeLongText;
use Sanam\Form\QuestionType\QuestionTypeNumber;
use Sanam\Form\QuestionType\QuestionTypeRadio;
use Sanam\Form\QuestionType\QuestionTypeRequester;
use Sanam\Form\QuestionType\QuestionTypeRequestType;
use Sanam\Form\QuestionType\QuestionTypeShortText;
use Sanam\Form\QuestionType\QuestionTypeUrgency;
use Sanam\Toolbox\SingletonTrait;
use InvalidArgumentException;

final class TypesConversionMapper
{
    use SingletonTrait;

    /** @var array<string, ?FormQuestionDataConverterInterface> */
    private array $questions_types_conversion_map;

    private function __construct()
    {
        $this->questions_types_conversion_map = $this->getDefaultQuestionTypesConverter();
    }

    /**
     * Retrieve the map of types to convert
     *
     * @return array
     */
    public function getQuestionTypesConversionMap(): array
    {
        return $this->questions_types_conversion_map;
    }

    public function registerPluginQuestionTypeConverter(
        string $formcreator_type,
        ?FormQuestionDataConverterInterface $converter
    ): void {
        if (!array_key_exists($formcreator_type, $this->questions_types_conversion_map)) {
            throw new InvalidArgumentException("Unknown type: `$formcreator_type`");
        }

        $this->questions_types_conversion_map[$formcreator_type] = $converter;
    }

    public function getPluginHintForType(string $type): ?string
    {
        return match ($type) {
            default      => null,
            "hidden"     => "advancedforms",
            "ldapselect" => "advancedforms",
            "ip"         => "advancedforms",
            "hostname"   => "advancedforms",
            "tag"        => "tag",
            "fields"     => "fields",
        };
    }

    /** @return array<string, ?FormQuestionDataConverterInterface> */
    private function getDefaultQuestionTypesConverter(): array
    {
        return [
            'checkboxes'  => new QuestionTypeCheckbox(),
            'date'        => new QuestionTypeDateTime(),
            'datetime'    => new QuestionTypeDateTime(),
            'dropdown'    => new QuestionTypeItemDropdown(),
            'email'       => new QuestionTypeEmail(),
            'file'        => new QuestionTypeFile(),
            'float'       => new QuestionTypeNumber(),
            'sanamselect'  => new QuestionTypeItem(),
            'integer'     => new QuestionTypeNumber(),
            'multiselect' => new QuestionTypeDropdown(),
            'radios'      => new QuestionTypeRadio(),
            'requesttype' => new QuestionTypeRequestType(),
            'select'      => new QuestionTypeDropdown(),
            'textarea'    => new QuestionTypeLongText(),
            'text'        => new QuestionTypeShortText(),
            'time'        => new QuestionTypeDateTime(),
            'urgency'     => new QuestionTypeUrgency(),

            // We do not have a question of type "Actor", we have more specific
            // types: "Assignee", "Requester" and "Observer".
            // Fallback to "Requester" as we can't guess the expected type.
            'actor'       => new QuestionTypeRequester(),

            // Description is replaced by a new block : Comment
            'description' => null,

            // Unsupported types, some of them might be implemented by plugins.
            'fields'      => null,
            'tag'         => null,
            'hidden'      => null,
            'hostname'    => null,
            'ip'          => null,
            'ldapselect'  => null,

            // Invalid type
            'undefined'   => null,
        ];
    }
}
