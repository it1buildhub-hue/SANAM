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

namespace Sanam\Controller\Form\Translation;

use Dropdown;
use Sanam\Application\View\TemplateRenderer;
use Sanam\Controller\AbstractController;
use Sanam\Exception\Http\AccessDeniedHttpException;
use Sanam\Exception\Http\BadRequestHttpException;
use Sanam\Exception\Http\NotFoundHttpException;
use Sanam\Form\Form;
use Sanam\Form\FormTranslation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GetFormTranslationController extends AbstractController
{
    #[Route("/Form/Translation/{form_id}/{language}", name: "sanam_get_form_translation", methods: "GET")]
    public function __invoke(Request $request, int $form_id, string $language): Response
    {
        // Retrieve the form from the database
        $form = new Form();
        if (!$form->getFromDB($form_id)) {
            throw new NotFoundHttpException('Form not found');
        }

        // Right check
        if (!$form->can($form_id, READ)) {
            throw new AccessDeniedHttpException();
        }

        // Validate the language code
        if (!\array_key_exists($language, Dropdown::getLanguages())) {
            throw new BadRequestHttpException('Invalid language code');
        }

        $form_translation = current(array_filter(
            FormTranslation::getTranslationsForItem($form),
            fn($translation) => $translation instanceof FormTranslation && $translation->fields['language'] === $language
        ));
        if ($form_translation === false) {
            throw new NotFoundHttpException('Form translation not found');
        }

        return new Response(TemplateRenderer::getInstance()->render(
            'pages/admin/form/form_translation_content.html.twig',
            [
                'form'             => $form,
                'form_translation' => $form_translation,
            ]
        ));
    }
}
