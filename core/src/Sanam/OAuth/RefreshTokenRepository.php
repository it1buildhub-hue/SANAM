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

use Sanam\DBAL\QuerySubQuery;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @return RefreshToken|null
     */
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        global $DB;

        // clean expired tokens
        $DB->delete('sanam_oauth_refresh_tokens', [
            'date_expiration' => ['<', date('Y-m-d H:i:s')],
        ]);

        $DB->insert('sanam_oauth_refresh_tokens', [
            'identifier' => $refreshTokenEntity->getIdentifier(),
            'access_token' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'date_expiration' => $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param string $tokenId
     *
     * @return void
     */
    public function revokeRefreshToken($tokenId): void
    {
        global $DB;

        $DB->delete('sanam_oauth_refresh_tokens', [
            'identifier' => $tokenId,
        ]);
    }

    /**
     * Revoke all refresh tokens whose linked access token was issued for the given client.
     *
     * @param string $clientIdentifier
     *
     * @return void
     */
    public function revokeByClient(string $clientIdentifier): void
    {
        global $DB;

        $DB->delete('sanam_oauth_refresh_tokens', [
            'access_token' => new QuerySubQuery([
                'SELECT' => 'identifier',
                'FROM'   => 'sanam_oauth_access_tokens',
                'WHERE'  => ['client' => $clientIdentifier],
            ]),
        ]);
    }

    /**
     * @param string $tokenId
     *
     * @return bool
     */
    public function isRefreshTokenRevoked($tokenId): bool
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'identifier',
            'FROM' => 'sanam_oauth_refresh_tokens',
            'WHERE' => [
                'identifier' => $tokenId,
            ],
        ]);

        return $iterator->count() === 0;
    }
}
