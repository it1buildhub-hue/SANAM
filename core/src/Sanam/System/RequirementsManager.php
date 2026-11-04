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

namespace Sanam\System;

use DBmysql;
use Sanam\System\Requirement\DbEngine;
use Sanam\System\Requirement\DbTimezones;
use Sanam\System\Requirement\DirectoriesWriteAccess;
use Sanam\System\Requirement\DirectoryWriteAccess;
use Sanam\System\Requirement\Extension;
use Sanam\System\Requirement\ExtensionConstant;
use Sanam\System\Requirement\ExtensionGroup;
use Sanam\System\Requirement\IntegerSize;
use Sanam\System\Requirement\LogsWriteAccess;
use Sanam\System\Requirement\MemoryLimit;
use Sanam\System\Requirement\PhpSupportedVersion;
use Sanam\System\Requirement\PhpVersion;
use Sanam\System\Requirement\SecurityKeysAccess;
use Sanam\System\Requirement\SeLinux;
use Sanam\System\Requirement\SessionsConfiguration;
use Sanam\System\Requirement\SessionsSecurityConfiguration;

/**
 * @since 9.5.0
 */
class RequirementsManager
{
    /**
     * Returns core requirement list.
     *
     * @param DBmysql|null $db DB instance (if null BD requirements will not be returned).
     *
     * @return RequirementsList
     */
    public function getCoreRequirementList(?DBmysql $db = null): RequirementsList
    {
        $requirements = [];

        $requirements[] = new PhpVersion(SANAM_MIN_PHP, SANAM_MAX_PHP);
        $requirements[] = new IntegerSize();

        $requirements[] = new SessionsConfiguration();

        $requirements[] = new MemoryLimit(64 * 1024 * 1024);

        // Mandatory PHP extensions that are enabled per default but can be disabled
        $requirements[] = new ExtensionGroup(
            __('PHP core extensions'),
            [
                'dom',
                'fileinfo',
                'filter',
                'libxml',
                'simplexml',
                'tokenizer', // required by `\Symfony\Component\Routing\Loader\AttributeFileLoader`
                'xmlreader', // required/used by simplepie/simplepie and sabre/xml
                'xmlwriter', // required/used by sabre/xml
            ]
        );

        // Mandatory PHP extensions that are NOT enabled per default
        $requirements[] = new Extension(
            'mysqli',
            false,
            __('Required for database access.')
        );
        $requirements[] = new Extension(
            'curl',
            false,
            __('Required for remote access to resources (inventory agent requests, marketplace, RSS feeds, ...).')
        );
        $requirements[] = new Extension(
            'gd',
            false,
            __('Required for images handling.')
        );
        $requirements[] = new Extension(
            'intl',
            false,
            __('Required for internationalization.')
        );
        $requirements[] = new Extension(
            'mbstring',
            false,
            __('Required for multibyte chars support and charset conversion.')
        );
        $requirements[] = new Extension(
            'zlib',
            false,
            __('Required for handling of compressed communication with inventory agents, installation of gzip packages from marketplace and PDF generation.')
        );
        $requirements[] = new Extension(
            'bcmath',
            false,
            __('Required for qrcode support')
        );
        $requirements[] = new ExtensionConstant(
            __('Sodium ChaCha20-Poly1305 size constant'),
            'SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES',
            false,
            __('Enable usage of ChaCha20-Poly1305 encryption required by SANAM. This is provided by libsodium 1.0.12 and newer.')
        );
        $requirements[] = new Extension(
            'openssl',
            false,
            __('Required for email sending using SSL/TLS, handling of encrypted communication with inventory agents and OAuth 2.0 authentication.')
        );

        if ($db instanceof DBmysql) {
            $requirements[] = new DbEngine($db);
        }

        global $PHPLOGGER;
        $requirements[] = new LogsWriteAccess($PHPLOGGER);

        $requirements[] = new DirectoriesWriteAccess(
            __('Permissions for SANAM data directories'),
            array_filter(
                Variables::getDataDirectories(),
                function ($directory) {
                    return $directory !== SANAM_LOG_DIR; // Specifically checked by LogsWriteAccess requirement
                }
            )
        );

        $requirements[] = new SecurityKeysAccess();

        $requirements[] = new SeLinux();

        // Below requirements are optionals

        $requirements[] = new PhpSupportedVersion();

        $requirements[] = new SessionsSecurityConfiguration();
        $requirements[] = new Extension(
            'exif',
            true,
            __('Enhance security on images validation.')
        );
        $requirements[] = new Extension(
            'ldap',
            true,
            __('Enable usage of authentication through remote LDAP server.')
        );
        $requirements[] = new ExtensionGroup(
            __('PHP extensions for marketplace'),
            ['bz2', 'Phar', 'zip'],
            true,
            __('Enable support of most common packages formats in marketplace.')
        );
        $requirements[] = new Extension(
            'Zend OPcache',
            true,
            __('Enhance PHP engine performances.')
        );
        $requirements[] = new ExtensionGroup(
            __('PHP emulated extensions'),
            ['ctype', 'iconv', 'sodium'],
            true,
            __('Slightly enhance performances.')
        );

        $requirements[] = new DirectoryWriteAccess(
            SANAM_MARKETPLACE_DIR,
            true,
            __('Enable installation of plugins from marketplace.')
        );

        if ($db instanceof DBmysql) {
            $requirements[] = new DbTimezones($db);
        }

        return new RequirementsList($requirements);
    }
}
