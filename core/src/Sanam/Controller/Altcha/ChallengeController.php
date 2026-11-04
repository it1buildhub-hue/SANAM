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

namespace Sanam\Controller\Altcha;

use Sanam\Altcha\AltchaManager;
use Sanam\Controller\AbstractController;
use Sanam\Http\Firewall;
use Sanam\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ChallengeController extends AbstractController
{
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    #[Route(
        "/Altcha/Challenge",
        name: "sanam_altcha_challenge",
        methods: "GET",
    )]
    public function __invoke(): Response
    {
        $challenge = AltchaManager::getInstance()->generateChallenge();
        return new JsonResponse($challenge);
    }
}
