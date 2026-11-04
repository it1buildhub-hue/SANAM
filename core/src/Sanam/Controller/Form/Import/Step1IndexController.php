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

namespace Sanam\Controller\Form\Import;

use Sanam\Controller\AbstractController;
use Sanam\Exception\Http\AccessDeniedHttpException;
use Sanam\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Initial entry point of the import form process.
 */
final class Step1IndexController extends AbstractController
{
    #[Route("/Form/Import", name: "sanam_form_import", methods: "GET")]
    public function __invoke(Request $request): Response
    {
        if (!Form::canCreate()) {
            throw new AccessDeniedHttpException();
        }

        return $this->render("pages/admin/form/import/step1_index.html.twig", [
            'title' => __("Import form"),
            'menu'  => ['admin', Form::getType()],
        ]);
    }
}
