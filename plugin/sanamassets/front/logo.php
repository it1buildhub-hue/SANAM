<?php

/**
 * Logo of a company. Served only to users who can access that company. Errors send a status
 * code and no body, so no file path is ever printed.
 */

include('../../../inc/includes.php');

use SanamPlugin\Sanamassets\Brand;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

if (!Session::getLoginUserID()) {
    return new Response('', 401);
}

$entity = filter_var($_GET['entity'] ?? null, FILTER_VALIDATE_INT);
if ($entity === false || $entity < 0) {
    return new Response('', 404);
}
if (!Session::haveAccessToEntity($entity)) {
    return new Response('', 403);
}

$logo = Brand::logo($entity);
if ($logo === null) {
    return new Response('', 404);
}

// Not public: the response is for this user only. The file is streamed, never named.
return new BinaryFileResponse(
    $logo['path'],
    200,
    [
        'Content-Type'           => $logo['mime'],
        'Cache-Control'          => 'private, max-age=3600',
        'X-Content-Type-Options' => 'nosniff',
    ],
    false
);
