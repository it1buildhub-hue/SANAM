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

use Document;
use Sanam\Controller\AbstractController;
use Sanam\Exception\Http\BadRequestHttpException;
use Sanam\UI\IllustrationManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Safe\realpath;

final class UploadController extends AbstractController
{
    public function __construct(
        private IllustrationManager $illustration_manager
    ) {}

    #[Route(
        "/UI/Illustration/Upload",
        name: "sanam_ui_illustration_upload",
        methods: "POST",
    )]
    public function __invoke(Request $request): Response
    {
        // Read parameters
        $file_name = $request->request->getString('filename', "");
        $file_path = realpath(SANAM_TMP_DIR . "/$file_name");

        if (
            empty($file_name)
            || !str_starts_with($file_path, realpath(SANAM_TMP_DIR))
            || !file_exists($file_path)
            || !Document::isImage($file_path)
        ) {
            throw new BadRequestHttpException();
        }

        // Never reuse the client-supplied filename as id: it would let a same-named re-upload overwrite this one at the same cached URL.
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $illustration_id = uniqid('', true) . ($extension !== '' ? ".$extension" : '');

        $this->illustration_manager->saveCustomIllustration($illustration_id, $file_path);
        return new JsonResponse(['file' => $illustration_id]);
    }
}
