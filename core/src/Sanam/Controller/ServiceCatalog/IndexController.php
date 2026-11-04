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

namespace Sanam\Controller\ServiceCatalog;

use Entity;
use Sanam\Controller\AbstractController;
use Sanam\Exception\Http\AccessDeniedHttpException;
use Sanam\Form\AccessControl\FormAccessParameters;
use Sanam\Form\ServiceCatalog\ItemRequest;
use Sanam\Form\ServiceCatalog\ServiceCatalog;
use Sanam\Form\ServiceCatalog\ServiceCatalogManager;
use Sanam\Form\ServiceCatalog\SortStrategy\SortStrategyEnum;
use Sanam\Http\Firewall;
use Sanam\Security\Attribute\SecurityStrategy;
use RuntimeException;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexController extends AbstractController
{
    private string $interface;
    private ServiceCatalogManager $service_catalog_manager;

    public function __construct()
    {
        // TODO: replace by autowiring once dependency injection is fully implemented.
        $this->service_catalog_manager = ServiceCatalogManager::getInstance();
        $this->interface = Session::getCurrentInterface();
    }

    #[SecurityStrategy(Firewall::STRATEGY_AUTHENTICATED)]
    #[Route(
        "/ServiceCatalog",
        name: "sanam_service_catalog",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        $session = Session::getCurrentSessionInfo();
        $parameters = new FormAccessParameters(
            session_info: Session::getCurrentSessionInfo(),
            url_parameters: $request->query->all()
        );

        // Make sure service catalog is enabled
        if ($session === null) {
            throw new AccessDeniedHttpException();
        }
        $entity = Entity::getById($session->getCurrentEntityId());
        if (!$entity) {
            // Safety check, will never happen but help with static analysis.
            throw new RuntimeException("Cant load current entity");
        }
        if (!$entity->isServiceCatalogEnabled()) {
            throw new AccessDeniedHttpException();
        }

        $item_request = new ItemRequest(
            access_parameters: $parameters,
            category_id: 0,
            sort_strategy: $entity->getServiceCatalogDefaultSortStrategy(),
        );
        $items = $this->service_catalog_manager->getItems($item_request);

        return $this->render('pages/self-service/service_catalog.html.twig', [
            'title' => __("New ticket"),
            'menu'  => $this->interface == "central"
                ? ["helpdesk", ServiceCatalog::class]
                : ["create_ticket"],
            'items' => $items,
            'sort_strategies' => SortStrategyEnum::getAvailableStrategies(),
            'default_sort_strategy' => $entity->getServiceCatalogDefaultSortStrategy()->value,
            'expand_categories' => $entity->shouldExpandCategoriesInServiceCatalog(),
        ]);
    }
}
