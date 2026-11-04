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

namespace Sanam\Application;

use Sanam\Altcha\AltchaManager;
use Sanam\Error\ErrorHandler;
use Sanam\Log\AccessLogHandler;
use Sanam\Log\ErrorLogHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Safe\Exceptions\InfoException;
use Safe\Exceptions\PcreException;

use function Safe\define;
use function Safe\ini_get;
use function Safe\ini_set;
use function Safe\mkdir;
use function Safe\preg_grep;
use function Safe\preg_match;
use function Safe\preg_replace_callback;
use function Safe\session_name;

final class SystemConfigurator
{
    private LoggerInterface $logger;

    public function __construct(private string $root_dir, private ?string $env)
    {
        $this->computeConstants();
        $this->initLogger();
        $this->registerErrorHandler();

        $this->setSessionConfiguration();

        // Keep it after `registerErrorHandler()` call to be sure that messages are correctly handled.
        $this->checkForObsoleteConstants();
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    private function computeConstants(): void
    {
        if ($this->env !== null) {
            // Force the `SANAM_ENVIRONMENT_TYPE` constant.
            // The value defined in the server env variables will be ignored.
            $env = Environment::from($this->env);
            Environment::set($env);
        }

        // Define SANAM_* constants that can be customized by admin.
        //
        // Use a self-invoking anonymous function to:
        // - prevent any global variables/functions definition from `local_define.php` and `downstream.php` files;
        // - prevent any global variables definition from current function logic.

        $constants = [
            'default' => [
                // SANAM environment
                'SANAM_ENVIRONMENT_TYPE' => Environment::PRODUCTION->value,

                // Constants related to system paths
                'SANAM_CONFIG_DIR'      => $this->root_dir . '/config', // Path for configuration files (db, security key, ...)
                'SANAM_VAR_DIR'         => $this->root_dir . '/files',  // Path for all files
                'SANAM_MARKETPLACE_DIR' => $this->root_dir . '/marketplace', // Path for marketplace plugins
                'SANAM_DOC_DIR'         => '{SANAM_VAR_DIR}', // Path for documents storage
                'SANAM_CACHE_DIR'       => '{SANAM_VAR_DIR}/_cache', // Path for cache
                'SANAM_CRON_DIR'        => '{SANAM_VAR_DIR}/_cron', // Path for cron storage
                'SANAM_GRAPH_DIR'       => '{SANAM_VAR_DIR}/_graphs', // Path for graph storage
                'SANAM_LOCAL_I18N_DIR'  => '{SANAM_VAR_DIR}/_locales', // Path for local i18n files
                'SANAM_LOCK_DIR'        => '{SANAM_VAR_DIR}/_lock', // Path for lock files storage (used by cron)
                'SANAM_LOG_DIR'         => '{SANAM_VAR_DIR}/_log', // Path for log storage
                'SANAM_PICTURE_DIR'     => '{SANAM_VAR_DIR}/_pictures', // Path for picture storage
                'SANAM_PLUGIN_DOC_DIR'  => '{SANAM_VAR_DIR}/_plugins', // Path for plugins documents storage
                'SANAM_RSS_DIR'         => '{SANAM_VAR_DIR}/_rss', // Path for rss storage
                'SANAM_SESSION_DIR'     => '{SANAM_VAR_DIR}/_sessions', // Path for sessions storage
                'SANAM_TMP_DIR'         => '{SANAM_VAR_DIR}/_tmp', // Path for temp storage
                'SANAM_UPLOAD_DIR'      => '{SANAM_VAR_DIR}/_uploads', // Path for upload storage
                "SANAM_INVENTORY_DIR"   => '{SANAM_VAR_DIR}/_inventories', //Path for inventories
                'SANAM_THEMES_DIR'      => '{SANAM_VAR_DIR}/_themes', // Path for custom themes storage

                // Where to load plugins.
                // Order in this array is important (priority to first found).
                'SANAM_PLUGINS_DIRECTORIES' => [
                    '{SANAM_MARKETPLACE_DIR}',
                    $this->root_dir . '/plugins',
                ],

                // Security constants
                'SANAM_ALLOW_IFRAME_IN_RICH_TEXT' => false,
                'SANAM_SERVERSIDE_URL_ALLOWLIST'  => [
                    // allowlist (regex format) of URL that can be fetched from server side (used for RSS feeds and external calendars, among others)
                    // URL will be considered as safe as long as it matches at least one entry of the allowlist

                    // Based on https://github.com/symfony/symfony/blob/7.3/src/Symfony/Component/Validator/Constraints/UrlValidator.php
                    '~^
                        (http|https|feed)://                                                # protocol
                        (
                            (?:
                                (?:xn--[a-z0-9-]++\.)*+xn--[a-z0-9-]++                      # a domain name using punycode
                                    |
                                (?:[\pL\pN\pS\pM\-\_]++\.)+[\pL\pN\pM]++                    # a multi-level domain name
                                    |
                                [a-z0-9\-\_]++                                              # a single-level domain name
                            )\.?
                                |                                                           # or
                            \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}                              # an IP address
                                |                                                           # or
                            \[
                                (?:(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){6})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:::(?:(?:(?:[0-9a-f]{1,4})):){5})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){4})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,1}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){3})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,2}(?:(?:[0-9a-f]{1,4})))?::(?:(?:(?:[0-9a-f]{1,4})):){2})(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,3}(?:(?:[0-9a-f]{1,4})))?::(?:(?:[0-9a-f]{1,4})):)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,4}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:(?:(?:(?:[0-9a-f]{1,4})):(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9]))\.){3}(?:(?:25[0-5]|(?:[1-9]|1[0-9]|2[0-4])?[0-9])))))))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,5}(?:(?:[0-9a-f]{1,4})))?::)(?:(?:[0-9a-f]{1,4})))|(?:(?:(?:(?:(?:(?:[0-9a-f]{1,4})):){0,6}(?:(?:[0-9a-f]{1,4})))?::))))
                            \]                                                              # an IPv6 address
                        )
                        (?:/ (?:[\pL\pN\pS\pM\-._\~!$&\'()*+,;=:@]|%[0-9A-Fa-f]{2})* )*     # a path
                        (?:\? (?:[\pL\pN\-._\~!$&\'\[\]()*+,;=:@/?]|%[0-9A-Fa-f]{2})* )?    # a query (optional)
                    $~ixuD',
                ],
                'SANAM_DISALLOWED_UPLOADS_PATTERN' => '/\.(php\d*|phar)$/i', // Prevent upload of any PHP file / PHP archive; can be set to an empty value to allow every files

                // Constants related to SANAM Project / SANAM Network external services
                'SANAM_TELEMETRY_URI'                => '#', // Telemetry project URL
                'SANAM_INSTALL_MODE'                 => is_dir($this->root_dir . '/.git') ? 'GIT' : 'TARBALL', // Install mode for telemetry
                'SANAM_NETWORK_MAIL'                 => 'sanam@teclib.com',
                'SANAM_NETWORK_SERVICES'             => '#', // SANAM Network services project URL
                'SANAM_NETWORK_API_URL'              => '{SANAM_NETWORK_SERVICES}/api', // SANAM Network API base URL
                'SANAM_NETWORK_REGISTRATION_API_URL' => '{SANAM_NETWORK_API_URL}/registration/',
                'SANAM_MARKETPLACE_ENABLE'           => 3, // 0 = Completely disabled, 1 = CLI only, 2 = Web only, 3 = CLI and Web
                'SANAM_MARKETPLACE_PLUGINS_API_URI'  => '{SANAM_NETWORK_API_URL}/marketplace/',
                'SANAM_MARKETPLACE_PRERELEASES'      => preg_match('/-(dev|alpha\d*|beta\d*|rc\d*)$/', SANAM_VERSION) === 1, // allow marketplace to expose unstable plugins versions
                'SANAM_MARKETPLACE_ALLOW_OVERRIDE'   => true, // allow marketplace to override a plugin found outside SANAM_MARKETPLACE_DIR
                'SANAM_MARKETPLACE_MANUAL_DOWNLOADS' => true, // propose manual download link of plugins which cannot be installed/updated by marketplace
                'SANAM_USER_AGENT_EXTRA_COMMENTS'    => '', // Extra comment to add to SANAM User-Agent
                'SANAM_DOCUMENTATION_ROOT_URL'       => '#', // Official documentations root URL

                // Constants dedicated to developers
                'SANAM_DISABLE_ONLY_FULL_GROUP_BY_SQL_MODE' => '1', // '1' to disable ONLY_FULL_GROUP_BY 'sql_mode'
                'SANAM_LOG_LVL'                             => LogLevel::WARNING,
                'SANAM_SKIP_UPDATES'                        => false, // `true` to bypass minor versions DB updates
                'SANAM_STRICT_ENV'                          => false, // `true` to make environment more strict (strict variables in twig templates, etc)

                // Other constants
                'SANAM_AJAX_DASHBOARD'         => '1', // 1 for "multi ajax mode" 0 for "single ajax mode" (see Sanam\Dashboard\Grid::getCards)
                'SANAM_CALDAV_IMPORT_STATE'    => 0, // external events created from a caldav client will take this state by default (0 = Planning::INFO)
                'SANAM_CENTRAL_WARNINGS'       => '1', // display (1), or not (0), warnings on SANAM Central page
                'SANAM_SYSTEM_CRON'            => false, // `true` to use the system cron provided by the downstream package
                'SANAM_TEXT_MAXSIZE'           => '4000', // character threshold for displaying read more button
                'SANAM_WEBHOOK_ALLOW_RESPONSE_SAVING' => '0', // allow (1) or not (0) to save webhook response in database
                'SANAM_WEBHOOK_CRA_MANDATORY' => false, // make challenge-response authentication mandatory or not for webhooks

                // Altcha
                'SANAM_ALTCHA_MODE'                => AltchaManager::DEFAULT_MODE,
                'SANAM_ALTCHA_MAX_NUMBER'          => AltchaManager::DEFAULT_COMPLEXITY,
                'SANAM_ALTCHA_EXPIRATION_INTERVAL' => AltchaManager::DEFAULT_EXPIRATION_INTERVAL,
            ],
        ];

        foreach (Environment::cases() as $env) {
            $constants[$env->value] = $env->getConstantsOverride($this->root_dir);
        }

        $constants_names = array_keys($constants['default']);

        // Define constants values based on server env variables (i.e. defined using apache SetEnv directive)
        foreach ($constants_names as $name) {
            if (!defined($name) && ($value = getenv($name)) !== false) {
                define($name, $value);
            }
        }

        // Define constants values from local configuration file
        if (file_exists($this->root_dir . '/config/local_define.php') && !defined('TU_USER')) {
            require_once $this->root_dir . '/config/local_define.php';
        }

        // Define constants values from downstream distribution file
        if (!defined('TU_USER') && file_exists($this->root_dir . '/inc/downstream.php')) {
            include_once($this->root_dir . '/inc/downstream.php');
        }

        // Handle deprecated/obsolete constants
        if (defined('PLUGINS_DIRECTORIES') && !defined('SANAM_PLUGINS_DIRECTORIES')) {
            if (!is_array(PLUGINS_DIRECTORIES)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Configuration "%s" must be an array, %s given.',
                        'PLUGINS_DIRECTORIES',
                        get_debug_type(PLUGINS_DIRECTORIES),
                    )
                );
            }

            define('SANAM_PLUGINS_DIRECTORIES', array_values(array_unique(PLUGINS_DIRECTORIES)));
        } elseif (defined('SANAM_PLUGINS_DIRECTORIES')) {
            $plugin_directories = constant('SANAM_PLUGINS_DIRECTORIES');
            if (!is_array($plugin_directories)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Configuration "%s" must be an array, %s given.',
                        'SANAM_PLUGINS_DIRECTORIES',
                        get_debug_type($plugin_directories),
                    )
                );
            }
        }

        // Configure environment type if not defined by user.
        if (Environment::isSet()) {
            Environment::validate();
        } else {
            Environment::set(Environment::PRODUCTION);
        }

        // Define constants values from defaults
        // 1. First, define constants that does not inherit from another one.
        // 2. Second, define constants that inherits from another one.
        // This logic is quiet simple and is not made to handle chain inheritance.
        $inherit_pattern = '/\{(?<name>SANAM_[\w]+)\}/';
        foreach ($constants_names as $name) {
            $value = $constants[SANAM_ENVIRONMENT_TYPE][$name] ?? $constants['default'][$name];
            if (!defined($name) && (!is_string($value) || !preg_match($inherit_pattern, $value))) {
                if (
                    (!is_string($value) && !is_array($value))
                    || (is_string($value) && !preg_match($inherit_pattern, $value))
                    || (is_array($value) && count(preg_grep($inherit_pattern, $value)) === 0)
                ) {
                    define($name, $value);
                }
            }
        }
        foreach ($constants_names as $name) {
            if (!defined($name)) {
                // Replace {SANAM_*} by value of corresponding constant
                $value = preg_replace_callback(
                    '/\{(?<name>SANAM_[\w]+)\}/',
                    fn($matches) => defined($matches['name']) ? constant($matches['name']) : '',
                    $constants[SANAM_ENVIRONMENT_TYPE][$name] ?? $constants['default'][$name]
                );

                if ($name === 'SANAM_PLUGINS_DIRECTORIES') {
                    if (!is_array($value)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'Configuration "%s" must be an array, %s given.',
                                $name,
                                get_debug_type($value),
                            )
                        );
                    }

                    $value = array_values(array_unique($value));
                }

                define($name, $value);
            }
        }

        // Try to create sub directories of `SANAM_VAR_DIR`, if they are not existing.
        // Silently fail, as handling errors is not really possible here.
        foreach ($constants_names as $name) {
            try {
                if (preg_match('/^SANAM_[\w]+_DIR$/', $name) !== 1) {
                    continue;
                }
            } catch (PcreException $e) {
                continue;
            }
            $value = constant($name);
            if (
                preg_match('/^' . preg_quote(SANAM_VAR_DIR, '/') . '\//', $value)
                && !is_dir($value)
            ) {
                @mkdir($value, recursive: true);
            }
        }
    }

    private function setSessionConfiguration(): void
    {
        if (PHP_SAPI === 'cli') {
            // Adapting session cookie params is useless in CLI mode.
            return;
        }

        // Set secure cookie config
        $target_configs = [
            'session.use_trans_sid'     => false,
            'session.use_only_cookies'  => true,
            'session.cookie_httponly'   => true,
        ];

        foreach ($target_configs as $name => $target_value) {
            $current_value = filter_var(ini_get($name), FILTER_VALIDATE_BOOLEAN);
            if ($current_value !== $target_value) {
                try {
                    ini_set($name, $target_value ? '1' : '0');
                } catch (InfoException $e) {
                    $this->logger->error(
                        sprintf(
                            'Unable to set `%s` to `%s`. You should enforce the value in your PHP configuration. Error is: %s',
                            $name,
                            $target_value ? '1' : '0',
                            $e->getMessage()
                        ),
                        ['exception' => $e]
                    );
                }
            }
        }

        // Force session cookie name.
        // The cookie name contains the root dir + HTTP host + HTTP port to ensure that it is unique
        // for every SANAM instance, enven if they are served by the same server (mostly for dev envs).
        session_name('sanam_' . \hash('sha512', $this->root_dir . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['SERVER_PORT'] ?? '')));
    }

    private function initLogger(): void
    {
        global $PHPLOGGER;

        $PHPLOGGER = new Logger('sanam');
        $PHPLOGGER->pushHandler(new ErrorLogHandler());
        $PHPLOGGER->pushHandler(new AccessLogHandler());

        $this->logger = $PHPLOGGER;
    }

    private function registerErrorHandler(): void
    {
        $errorHandler = new ErrorHandler($this->logger);
        $errorHandler::register($errorHandler);
    }

    private function checkForObsoleteConstants(): void
    {
        if (defined('SANAM_USE_CSRF_CHECK')) {
            trigger_error(
                'The `SANAM_USE_CSRF_CHECK` constant is now ignored for security reasons.',
                E_USER_WARNING
            );
        }

        if (defined('PLUGINS_DIRECTORIES')) {
            trigger_error(
                'The `PLUGINS_DIRECTORIES` constant is deprecated. Use the `SANAM_PLUGINS_DIRECTORIES` constant instead.',
                E_USER_DEPRECATED
            );
        }
    }
}
