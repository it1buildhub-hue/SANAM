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

use Config;
use DBConnection;
use Sanam\Application\Environment;
use Sanam\Debug\Profiler;
use Sanam\Kernel\ListenersPriority;
use Sanam\Kernel\PostBootEvent;
use Plugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class CheckPluginsStates implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PostBootEvent::class => ['onPostBoot', ListenersPriority::POST_BOOT_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onPostBoot(): void
    {
        global $DB;
        if (
            !DBConnection::isDbAvailable()
            || !Config::isLegacyConfigurationLoaded()
            || !$DB->tableExists(Plugin::getTable())
        ) {
            return;
        }

        Profiler::getInstance()->start('CheckPluginsStates::execute', Profiler::CATEGORY_BOOT);

        // Must be done before checking the plugins states, as the state check is responsible for loading
        // the plugin `setup.php` file, which declares the plugin hooks (`plugin_tester_boot()`, ...).
        if (Environment::get()->shouldSetupTesterPlugin()) {
            $this->setupTesterPlugin();
        }

        (new Plugin())->checkStates();

        Profiler::getInstance()->stop('CheckPluginsStates::execute');
    }

    private function setupTesterPlugin(): void
    {
        global $DB;
        $DB->updateOrInsert(table: Plugin::getTable(), params: [
            'directory' => 'tester',
            'name'      => 'tester',
            'state'     => 1,
            'version'   => '1.0.0',
        ], where: ['directory' => 'tester']);
    }
}
