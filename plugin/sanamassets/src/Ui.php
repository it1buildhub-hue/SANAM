<?php

namespace SanamPlugin\Sanamassets;

use Session;
use Throwable;

/** Small helpers shared by the plugin pages. */
final class Ui
{
    public static function e(mixed $value): string
    {
        return htmlescape((string) $value);
    }

    /**
     * Path of the current page without the query string. Not $_SERVER['PHP_SELF']: the PHP
     * built-in server behind a router reports "\index.php" there, which breaks redirects and forms.
     */
    public static function self(): string
    {
        return (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    }

    public static function csrf(): string
    {
        return '<input type="hidden" name="_sanam_csrf_token" value="' . self::e(Session::getNewCSRFToken()) . '">';
    }

    /**
     * Run a state-changing action. Success or the domain error is shown after the redirect.
     */
    public static function run(callable $action, string $success): bool
    {
        try {
            $action();
            Session::addMessageAfterRedirect($success, false, INFO);
            return true;
        } catch (Throwable $e) {
            Session::addMessageAfterRedirect($e->getMessage(), false, ERROR);
            return false;
        }
    }

    /** Label + value card row used on the asset summary. */
    public static function field(string $label, mixed $value): string
    {
        $shown = ($value === null || $value === '' || $value === 0 || $value === '0') ? '—' : (string) $value;
        return '<div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">'
            . self::e($label) . '</span><span class="fw-semibold text-end">' . self::e($shown) . '</span></div>';
    }
}
