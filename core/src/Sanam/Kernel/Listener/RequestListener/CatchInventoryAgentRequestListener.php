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

use Sanam\Controller\InventoryController;
use Sanam\Kernel\KernelListenerTrait;
use Sanam\Kernel\ListenersPriority;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class CatchInventoryAgentRequestListener implements EventSubscriberInterface
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
        $request = $event->getRequest();

        if ($this->isControllerAlreadyAssigned($request)) {
            // A controller has already been assigned by a previous listener, do not override it.
            return;
        }

        if (
            $request->getPathInfo() === '/'
            && $request->isMethod('POST')
            && !$request->request->has('totp_code')
            && $request->getContent() !== ''
        ) {
            $sub_request = $request->duplicate();

            // Setting the `_controller` attribute will force Symfony to consider that routing was resolved already.
            // @see `\Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest()`
            $sub_request->attributes->set('_controller', InventoryController::class . '::index');

            $response = $event->getKernel()->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
            $event->setResponse($response);
        }
    }
}
