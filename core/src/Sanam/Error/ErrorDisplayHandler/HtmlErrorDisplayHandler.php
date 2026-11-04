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

namespace Sanam\Error\ErrorDisplayHandler;

use Sanam\Application\Environment;
use Session;
use Symfony\Component\HttpFoundation\Request;

final class HtmlErrorDisplayHandler implements ErrorDisplayHandler
{
    private static ?Request $currentRequest = null;

    public static function setCurrentRequest(Request $request): void
    {
        self::$currentRequest = $request;
    }

    public function canOutput(): bool
    {
        if (self::$currentRequest === null) {
            return false;
        }

        return self::$currentRequest->getPreferredFormat() === 'html';
    }

    public function displayErrorMessage(string $error_label, string $message, string $log_level): void
    {
        $is_env_with_debug_tools = Environment::get()->shouldEnableExtraDevAndDebugTools();
        $is_debug_mode = isset($_SESSION['sanam_use_mode']) && $_SESSION['sanam_use_mode'] == Session::DEBUG_MODE;
        if (!$is_debug_mode && !$is_env_with_debug_tools) {
            // Do not display messages if debug mode is not active and if the environment should not enable debug tools.
            return;
        }

        echo \sprintf(
            '<div class="alert alert-important alert-danger sanam-debug-alert"><span class="fw-bold">%s: </span>%s</div>',
            \htmlescape($error_label),
            \htmlescape($message)
        );
    }
}
