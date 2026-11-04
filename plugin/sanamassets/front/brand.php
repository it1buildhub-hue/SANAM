<?php

/**
 * Branding of the active company, loaded by every logged-in page:
 *   brand.php?format=css   CSS for the logo variables (linked from the page head)
 *   brand.php?format=json  display name for the page title (read by public/js/brand.js)
 * Both are empty when the active company has no branding.
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\Brand;
use Symfony\Component\HttpFoundation\Response;

$headers = ['Cache-Control' => 'private, no-cache'];

if (($_GET['format'] ?? 'css') === 'json') {
    return new Response(
        json_encode(Brand::pageInfo(), JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP),
        200,
        $headers + ['Content-Type' => 'application/json; charset=UTF-8']
    );
}

return new Response(Brand::css(), 200, $headers + ['Content-Type' => 'text/css; charset=UTF-8']);
