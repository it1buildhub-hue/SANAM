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

namespace Sanam\Helpdesk\Tile;

use CommonDBTM;
use Sanam\Helpdesk\HelpdeskTranslation;
use Sanam\ItemTranslation\Context\ProvideTranslationsInterface;
use Sanam\ItemTranslation\Context\TranslationHandler;
use Sanam\Session\SessionInfo;
use Sanam\Toolbox\URL;
use Sanam\UI\IllustrationManager;
use Override;

final class ExternalPageTile extends CommonDBTM implements TileInterface, ProvideTranslationsInterface
{
    use TileRightTrait;

    public static $rightname = 'config';

    public const TRANSLATION_KEY_TITLE = 'title';
    public const TRANSLATION_KEY_DESCRIPTION = 'description';

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function getLabel(): string
    {
        return __("External page");
    }

    #[Override]
    public function getTitle(): string
    {
        return HelpdeskTranslation::translate(
            $this,
            self::TRANSLATION_KEY_TITLE
        ) ?? '';
    }

    #[Override]
    public function getDescription(): string
    {
        return HelpdeskTranslation::translate(
            $this,
            self::TRANSLATION_KEY_DESCRIPTION
        ) ?? '';
    }

    #[Override]
    public function getIllustration(): string
    {
        return $this->fields['illustration'] ?? IllustrationManager::DEFAULT_ILLUSTRATION;
    }

    #[Override]
    public function getTileUrl(): string
    {
        return URL::sanitizeURL($this->fields['url'] ?? '');
    }

    #[Override]
    public function isAvailable(SessionInfo $session_info): bool
    {
        return true;
    }

    #[Override]
    public function getDatabaseId(): int
    {
        return $this->fields['id'];
    }

    #[Override]
    public function getConfigFieldsTemplate(): string
    {
        return "pages/admin/external_page_tile_config_fields.html.twig";
    }

    #[Override]
    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Item_Tile::class,
            ]
        );
    }

    #[Override]
    public function listTranslationsHandlers(): array
    {
        $handlers = [];
        $key = sprintf('%s_%d', self::getType(), $this->getID());
        $category_name = sprintf('%s: %s', $this->getLabel(), $this->fields['title'] ?? NOT_AVAILABLE);
        if (!empty($this->fields['title'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_TITLE,
                name: __('Title'),
                value: $this->fields['title'],
                is_rich_text: false,
                category: $category_name
            );
        }
        if (!empty($this->fields['description'])) {
            $handlers[$key][] = new TranslationHandler(
                item: $this,
                key: self::TRANSLATION_KEY_DESCRIPTION,
                name: __('Description'),
                value: $this->fields['description'],
                is_rich_text: true,
                category: $category_name
            );
        }

        return $handlers;
    }
}
