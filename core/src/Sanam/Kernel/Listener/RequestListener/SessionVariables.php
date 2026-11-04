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

namespace Sanam\Kernel\Listener\RequestListener;

use Sanam\Kernel\KernelListenerTrait;
use Sanam\Kernel\ListenersPriority;
use Sanam\Toolbox\URL;
use Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SessionVariables implements EventSubscriberInterface
{
    use KernelListenerTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', ListenersPriority::REQUEST_LISTENERS_PRIORITIES[self::class]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        global $SANAM_CACHE;

        if (!$event->isMainRequest()) {
            // Do not change sessionv ariables on sub-requests.
            return;
        }

        if (!$this->isDatabaseUsable()) {
            // Do not try to load data from the database if it is not available.
            return;
        }

        $request = $event->getRequest();

        // Manage force tab
        if ($request->query->has('forcetab')) {
            $itemtype = URL::extractItemtypeFromUrlPath($request->getPathInfo());
            if ($itemtype !== null) {
                Session::setActiveTab($itemtype, $request->get('forcetab'));
            }
        }

        // Manage tabs
        if ($request->get('sanam_tab') && $request->get('itemtype')) {
            Session::setActiveTab($request->get('itemtype'), $request->get('sanam_tab'));
        }

        // Override list-limit if choosen
        if ($request->get('sanamlist_limit')) {
            $_SESSION['sanamlist_limit'] = $request->get('sanamlist_limit');
        }

        // Manage forced profile / entity.
        // This feature permits to craft custom links from an external app/notification that forces a specific profile
        // and/or a specific entity to be loaded.
        // see #10074
        $forced_profile = $request->get('force_profile');
        $forced_entity  = $request->get('force_entity');
        $check_entities = true;
        if (
            $forced_profile !== null
            && ($_SESSION['sanamactiveprofile']['id'] ?? -1) != $forced_profile
            && isset($_SESSION['sanamprofiles'][$forced_profile])
        ) {
            Session::changeProfile($forced_profile);
        }
        if (
            $forced_entity !== null
            && ($_SESSION["sanamactive_entity"] ?? -1) != $forced_entity
        ) {
            Session::changeActiveEntities($forced_entity, true);
            $check_entities = false;
        }

        // Reload entities if necessary.
        if ($check_entities && Session::shouldReloadActiveEntities()) {
            Session::changeActiveEntities(
                $_SESSION["sanamactive_entity"],
                $_SESSION["sanamactive_entity_recursive"]
            );
        }

        // The user's current groups are stored in his session
        // If there was any change regarding groups membership and/or configuration, we
        // need to reset the data stored in his session
        if (
            isset($_SESSION['sanamgroups'])
            && (
                !isset($_SESSION['sanamgroups_cache_date'])
                || $_SESSION['sanamgroups_cache_date'] < $SANAM_CACHE->get('last_group_change')
            )
        ) {
            Session::loadGroups();
        }
    }
}
