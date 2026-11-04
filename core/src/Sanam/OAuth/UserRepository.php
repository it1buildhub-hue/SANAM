<?php

/**
 * ---------------------------------------------------------------------
 *
 * SANAM
 *
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Sanam\OAuth;

use Auth;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    /**
     * @param string $username
     * @param string $password
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     *
     * @return ?UserEntityInterface
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity): ?UserEntityInterface
    {
        $auth = new Auth();
        $valid_login = $auth->validateLogin($username, $password, true);

        if (!$valid_login || !$auth->applyValidatedLogin() || empty($auth->user->fields['id'])) {
            return null;
        }

        $user_entity = new User();
        $user_entity->setIdentifier($auth->user->fields['id']);
        return $user_entity;
    }
}
