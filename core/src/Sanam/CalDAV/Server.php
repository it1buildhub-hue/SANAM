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

namespace Sanam\CalDAV;

use Sanam\CalDAV\Backend\Auth;
use Sanam\CalDAV\Backend\Calendar;
use Sanam\CalDAV\Backend\Principal;
use Sanam\CalDAV\Node\CalendarRoot;
use Sanam\CalDAV\Plugin\Acl;
use Sanam\CalDAV\Plugin\Browser;
use Sanam\CalDAV\Plugin\CalDAV;
use Sanam\Error\ErrorHandler;
use Sabre\DAV;
use Sabre\DAV\Auth\Plugin;
use Sabre\DAV\Exception;
use Sabre\DAV\SimpleCollection;
use Sabre\DAVACL\PrincipalCollection;
use Throwable;

class Server extends DAV\Server
{
    public function __construct()
    {
        $this->on('exception', [$this, 'logException']);

        // Backends
        $authBackend = new Auth();
        $principalBackend = new Principal();
        $calendarBackend = new Calendar();

        // Directory tree
        $tree = [
            new SimpleCollection(
                Principal::PRINCIPALS_ROOT,
                [
                    new PrincipalCollection($principalBackend, Principal::PREFIX_GROUPS),
                    new PrincipalCollection($principalBackend, Principal::PREFIX_USERS),
                ]
            ),
            new SimpleCollection(
                Calendar::CALENDAR_ROOT,
                [
                    new CalendarRoot($principalBackend, $calendarBackend, Principal::PREFIX_GROUPS),
                    new CalendarRoot($principalBackend, $calendarBackend, Principal::PREFIX_USERS),
                ]
            ),
        ];

        parent::__construct($tree);

        $this->addPlugin(new Plugin($authBackend));
        $this->addPlugin(new Acl());
        $this->addPlugin(new CalDAV());

        // Support for html frontend (only in debug mode)
        $this->addPlugin(new Browser(false));
    }

    /**
     * @param Throwable $exception
     *
     * @return void
     */
    public function logException(Throwable $exception)
    {
        if ($exception instanceof Exception && $exception->getHTTPCode() < 500) {
            // Ignore server exceptions that does not corresponds to a server error
            return;
        }

        ErrorHandler::logCaughtException($exception);
    }
}
