<?php

/**
 * SANAM Assets plugin.
 *
 * Adds a single authoritative asset identity (SNM-000001...), QR labels,
 * mobile scanning, physical audit campaigns, custody/handover and
 * SANAM-specific dashboards on top of the native SANAM 11 asset types.
 *
 * @license GPLv3+
 */

use Sanam\Plugin\Hooks;
use SanamPlugin\Sanamassets\AssetHooks;
use SanamPlugin\Sanamassets\Brand;
use SanamPlugin\Sanamassets\Menu;

define('PLUGIN_SANAMASSETS_VERSION', '1.0.0');
define('PLUGIN_SANAMASSETS_MIN_SANAM', '11.0.0');
define('PLUGIN_SANAMASSETS_MAX_SANAM', '11.0.99');

function plugin_init_sanamassets()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['sanamassets']  = true;
    $PLUGIN_HOOKS[Hooks::REDEFINE_MENUS]['sanamassets']  = [Menu::class, 'redefine'];

    // Company logo and title. The header hook takes a list of static tags (not HTML): the logo CSS
    // is loaded from front/brand.php, and public/js/brand.js sets the company name in the title.
    $PLUGIN_HOOKS[Hooks::ADD_HEADER_TAG]['sanamassets'] = Brand::headerTags();
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['sanamassets']  = ['js/brand.js'];

    // Item hooks are dispatched by exact class name, so each managed class is registered explicitly.
    foreach (AssetHooks::managedClasses() as $class) {
        $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['sanamassets'][$class]    = [AssetHooks::class, 'preItemAdd'];
        $PLUGIN_HOOKS[Hooks::ITEM_ADD]['sanamassets'][$class]        = [AssetHooks::class, 'itemAdd'];
        $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['sanamassets'][$class] = [AssetHooks::class, 'preItemUpdate'];
        $PLUGIN_HOOKS[Hooks::ITEM_PURGE]['sanamassets'][$class]      = [AssetHooks::class, 'itemPurge'];
    }
}

function plugin_version_sanamassets()
{
    return [
        'name'         => 'SANAM Assets',
        'version'      => PLUGIN_SANAMASSETS_VERSION,
        'author'       => 'SANAM IT',
        'license'      => 'GPLv3+',
        'homepage'     => '',
        'requirements' => [
            'sanam' => [
                'min' => PLUGIN_SANAMASSETS_MIN_SANAM,
                'max' => PLUGIN_SANAMASSETS_MAX_SANAM,
            ],
        ],
    ];
}

function plugin_sanamassets_check_prerequisites()
{
    if (version_compare(SANAM_VERSION, PLUGIN_SANAMASSETS_MIN_SANAM, '<')) {
        echo 'This plugin requires SANAM >= ' . PLUGIN_SANAMASSETS_MIN_SANAM;
        return false;
    }
    return true;
}

function plugin_sanamassets_check_config($verbose = false)
{
    return true;
}
