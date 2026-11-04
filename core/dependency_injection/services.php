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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sanam\DependencyInjection\PublicService;
use Sanam\Error\ErrorHandler;

return static function (ContainerConfigurator $container): void {
    $projectDir = dirname(__DIR__);
    $parameters = $container->parameters();
    $services = $container->services();

    // Default secret, just in case
    $parameters->set('sanam.default_secret', bin2hex(random_bytes(32)));
    $parameters->set('env(APP_SECRET_FILE)', $projectDir . '/config/sanamcrypt.key');
    $parameters->set('kernel.secret', env('default:sanam.default_secret:file:APP_SECRET_FILE'));

    // Prevent low level errors (e.g. warning) to be converted to exception in dev environment
    $parameters->set('debug.error_handler.throw_at', ErrorHandler::FATAL_ERRORS);

    $services = $services
        ->defaults()
            ->autowire()
            ->autoconfigure()
        ->instanceof(PublicService::class)->public()
    ;

    $services->load('Sanam\Controller\\', $projectDir . '/src/Sanam/Controller');
    $services->load('Sanam\Http\\', $projectDir . '/src/Sanam/Http');
    $services->load('Sanam\Kernel\\Listener\\', $projectDir . '/src/Sanam/Kernel/Listener');
    $services->load('Sanam\DependencyInjection\\', $projectDir . '/src/Sanam/DependencyInjection');
    $services->load('Sanam\Progress\\', $projectDir . '/src/Sanam/Progress')
        ->exclude([
            $projectDir . '/src/Sanam/Progress/ConsoleProgressIndicator.php',
            $projectDir . '/src/Sanam/Progress/StoredProgressIndicator.php',
        ]);
    $services->load(
        'Sanam\Form\Condition\\',
        $projectDir . '/src/Sanam/Form/Condition/*Manager.php'
    );
    $services->load(
        'Sanam\UI\\',
        $projectDir . '/src/Sanam/UI/*Manager.php'
    );

    // Prevent Symfony to register its own default logger.
    // @see \Symfony\Component\HttpKernel\DependencyInjection\LoggerPass
    $services->set('logger')->synthetic();
};
