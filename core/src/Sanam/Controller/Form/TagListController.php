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

namespace Sanam\Controller\Form;

use Sanam\Controller\AbstractController;
use Sanam\Exception\Http\AccessDeniedHttpException;
use Sanam\Exception\Http\BadRequestHttpException;
use Sanam\Exception\Http\NotFoundHttpException;
use Sanam\Form\Form;
use Sanam\Form\Tag\FormTagsManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TagListController extends AbstractController
{
    #[Route(
        "/Form/TagList",
        name: "sanam_form_tags_list",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        if (!Form::canUpdate()) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->loadSubmittedForm($request);
        $filter = $request->query->getString('filter');

        $tag_manager = new FormTagsManager();
        return new JsonResponse($tag_manager->getTags($form, $filter));
    }

    private function loadSubmittedForm(Request $request): Form
    {
        $forms_id = $request->query->getInt("form_id");
        if (!$forms_id) {
            throw new BadRequestHttpException();
        }

        $form = Form::getById($forms_id);
        if (!$form instanceof Form) {
            throw new NotFoundHttpException();
        }

        return $form;
    }
}
