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

namespace Sanam\Controller\Form\Destination;

use Sanam\Controller\AbstractController;
use Sanam\Exception\Http\AccessDeniedHttpException;
use Sanam\Exception\Http\BadRequestHttpException;
use Sanam\Form\Destination\FormDestination;
use Sanam\Form\Form;
use Sanam\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PurgeDestinationController extends AbstractController
{
    #[Route("/Form/{form_id}/Destination/{destination_id}/Purge", name: "sanam_form_destination_purge", methods: "POST")]
    public function __invoke(Request $request, int $form_id, int $destination_id): Response
    {
        $destination = new FormDestination();
        $input = array_merge($request->request->all(), ['id' => $destination_id]);

        // Right check
        if (!$destination->can($destination_id, DELETE, $input)) {
            throw new AccessDeniedHttpException();
        }

        // Delete destination item
        if (!$destination->delete($input)) {
            throw new BadRequestHttpException('Failed to delete destination item');
        }

        return new RedirectResponse(Form::getFormURLWithID($form_id));
    }
}
