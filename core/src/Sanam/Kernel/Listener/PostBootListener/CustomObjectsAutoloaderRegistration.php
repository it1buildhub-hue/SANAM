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

namespace Sanam\Kernel\Listener\PostBootListener;

use Sanam\Asset\AssetDefinitionManager;
use Sanam\Debug\Profiler;
use Sanam\Dropdown\DropdownDefinitionManager;
use Sanam\Kernel\KernelListenerTrait;
use Sanam\Kernel\ListenersPriority;
use Sanam\Kernel\PostBootEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class CustomObjectsAutoloaderRegistration implements EventSubscriberInterface
{
    use KernelListenerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        if (!$this->isDatabaseUsable()) {
            // Requires the database to be available.
            return;
        }

        Profiler::getInstance()->start('CustomObjectsAutoloaderRegistration::execute', Profiler::CATEGORY_BOOT);

        AssetDefinitionManager::getInstance()->registerAutoload();
        DropdownDefinitionManager::getInstance()->registerAutoload();

        Profiler::getInstance()->stop('CustomObjectsAutoloaderRegistration::execute');
    }
}
