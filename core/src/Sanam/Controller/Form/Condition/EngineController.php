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

namespace Sanam\Controller\Form\Condition;

use Sanam\Controller\AbstractController;
use Sanam\Controller\Form\Utils\CanCheckAccessPolicies;
use Sanam\Exception\Http\NotFoundHttpException;
use Sanam\Form\Condition\Engine;
use Sanam\Form\Condition\EngineInput;
use Sanam\Form\Form;
use Sanam\Http\Firewall;
use Sanam\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EngineController extends AbstractController
{
    use CanCheckAccessPolicies;

    #[Route(
        "/Form/Condition/Engine",
        name: "sanam_form_condition_engine",
        methods: "POST"
    )]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function __invoke(Request $request): Response
    {
        // Load target form
        $form_id = $request->request->getInt('form_id');
        $form = Form::getById($form_id);
        if (!$form) {
            throw new NotFoundHttpException();
        }

        $this->checkFormAccessPolicies($form, $request);

        // Load engine input
        $input = new EngineInput(
            answers: $request->request->all()['answers'] ?? [],
        );

        // Compute visibility
        $engine = new Engine($form, $input);
        return new JsonResponse($engine->computeVisibility());
    }
}
