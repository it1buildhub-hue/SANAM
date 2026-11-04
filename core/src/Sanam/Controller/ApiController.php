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

namespace Sanam\Controller;

use Sanam\Api\APIRest;
use Sanam\Api\HL\Controller\AbstractController as ApiAbstractController;
use Sanam\Api\HL\Router;
use Sanam\Api\HL\StreamedResponseWrapper;
use Sanam\Error\ErrorHandler;
use Sanam\Http\HeaderlessStreamedResponse;
use Sanam\Http\JSONResponse;
use Sanam\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use function Safe\file_get_contents;
use function Safe\preg_match;
use function Safe\preg_replace;

final class ApiController extends AbstractController
{
    #[Route(
        "/api.php{request_parameters}",
        name: "sanam_api",
        requirements: [
            'request_parameters' => '.*',
        ]
    )]
    public function __invoke(SymfonyRequest $request): SymfonyResponse
    {
        $_SERVER['PATH_INFO'] = $request->get('request_parameters');

        $method = $_SERVER['REQUEST_METHOD'];
        $relative_uri = $_SERVER['PATH_INFO'] ?? '';
        // Ensure uri starts with slash but does not end with a slash
        $relative_uri = '/' . trim($relative_uri, '/');

        // If the relative URI starts with /v1/ or is /v1 then we are dealing with a legacy API request
        if (preg_match('/^\/v1(\/|$)/', $relative_uri)) {
            // @phpstan-ignore-next-line method.deprecatedClass (refactoring is planned later)
            return new HeaderlessStreamedResponse(function () {
                $api = new APIRest();
                $api->call();
            });
        }

        // Extract the requested API version (if any) and then remove it from the URI
        $version = Router::API_VERSION;
        if (preg_match('/^\/v(\d+(?:\.\d+)*)\//', $relative_uri, $matches)) {
            $version = $matches[1];
            $relative_uri = preg_replace('/^\/v\d+(?:\.\d+)*\//', '/', $relative_uri);
        }
        $version = Router::normalizeAPIVersion($version);

        $body = file_get_contents('php://input');

        $headers = getallheaders();
        $headers['SANAM-API-Version'] = $version;
        $request = new Request($method, $relative_uri, $headers, $body);

        $router = Router::getInstance();

        try {
            $response = $router->handleRequest($request);
        } catch (InvalidArgumentException $e) {
            $response = new JSONResponse(
                ApiAbstractController::getErrorResponseBody(
                    ApiAbstractController::ERROR_INVALID_PARAMETER,
                    $e->getMessage()
                ),
                400
            );
        } catch (Throwable $e) {
            ErrorHandler::logCaughtException($e);
            $response = new JSONResponse(null, 500);
        }

        if ($response instanceof StreamedResponseWrapper) {
            return $response->getSymfonyResponse();
        }

        return new SymfonyResponse(
            (string) $response->getBody(),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }
}
