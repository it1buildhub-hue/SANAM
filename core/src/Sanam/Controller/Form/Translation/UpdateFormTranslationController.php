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

use Sanam\Controller\Translation\AbstractTranslationController;
use Sanam\Exception\Http\NotFoundHttpException;
use Sanam\Form\Form;
use Sanam\Form\FormTranslation;
use Sanam\ItemTranslation\CldrLanguage;
use Sanam\ItemTranslation\ItemTranslation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateFormTranslationController extends AbstractTranslationController
{
    private Form $form;

    #[Route("/Form/Translation/{form_id}/{language}", name: "sanam_update_form_translation", methods: "POST")]
    public function __invoke(Request $request, int $form_id, string $language): Response
    {
        // Retrieve the form from the database
        $this->form = new Form();
        if (!$this->form->getFromDB($form_id)) {
            throw new NotFoundHttpException('Form not found');
        }

        // Validate the language code
        $this->validateLanguage($language);

        $cldr_language = new CldrLanguage($language);
        $category_index = $cldr_language->getPluralKey(1);

        $input = $request->toArray();
        $input['translations'] = $this->remapFileUploadsToTranslations($category_index, $input);

        if ($this->processTranslations($input['translations'], $language)) {
            $this->addSuccessMessage($language);
        }

        return new JsonResponse([
            'redirect' => $this->getRedirectUrl(),
        ]);
    }

    protected function getTranslationClass(): ItemTranslation
    {
        return new FormTranslation();
    }

    protected function getRedirectUrl(?string $language = null): string
    {
        return $this->form->getFormURLWithID($this->form->getID());
    }

    protected function getTranslationHandlers(): array
    {
        return $this->form->listTranslationsHandlers();
    }

    protected function getContextTranslations(?string $language = null): array
    {
        return FormTranslation::getTranslationsForItem($this->form);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function remapFileUploadsToTranslations(string $category_index, array $input): array
    {
        $translations = $input['translations'] ?? [];
        $file_upload_prefixes = ['_', '_prefix_', '_tag_'];

        foreach ($file_upload_prefixes as $prefix) {
            $prefixed_key = $prefix . 'translations';
            if (!isset($input[$prefixed_key])) {
                continue;
            }

            foreach ($input[$prefixed_key] as $translation_key => $data) {
                if (!is_array($data) || !isset($data['translations'][$category_index])) {
                    continue;
                }

                $translations[$translation_key]['translations'][$prefix . $category_index] = $data['translations'][$category_index];
            }
        }

        return $translations;
    }
}
