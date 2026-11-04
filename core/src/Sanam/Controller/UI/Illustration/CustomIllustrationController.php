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

namespace Sanam\Controller\UI\Illustration;

use Sanam\Controller\AbstractController;
use Sanam\Exception\Http\BadRequestHttpException;
use Sanam\Http\Firewall;
use Sanam\Security\Attribute\SecurityStrategy;
use Sanam\UI\IllustrationManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomIllustrationController extends AbstractController
{
    private const CACHE_MAX_AGE = 60 * 60 * 24 * 365;

    public function __construct(
        private IllustrationManager $illustration_manager
    ) {}

    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        "/UI/Illustration/CustomIllustration/{id}",
        name: "sanam_ui_illustration_custom_illustration",
        methods: "GET",
    )]
    public function __invoke(string $id, Request $request): Response
    {
        $file = $this->illustration_manager->getCustomIllustrationFile($id);
        if (!$file) {
            throw new BadRequestHttpException();
        }

        // Clear the no-cache headers sent by the session cache limiter, Symfony only appends its own.
        header_remove('Cache-Control');
        header_remove('Expires');
        header_remove('Pragma');

        // Id is immutable per upload (see UploadController), safe to cache long-term.
        $response = new BinaryFileResponse($file);
        $response->setAutoEtag();
        $response->setAutoLastModified();
        $response->setCache([
            'private' => true,
            'immutable' => true,
            'max_age' => self::CACHE_MAX_AGE,
        ]);

        // Turns the response into a 304 when the client's cached ETag/Last-Modified still matches.
        $response->isNotModified($request);

        return $response;
    }
}
