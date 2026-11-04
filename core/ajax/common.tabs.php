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

use Sanam\Debug\Profiler;

global $CFG_SANAM;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// Session check is disabled for this script (see `\Sanam\Http\Firewall::computeStrategyForCoreLegacyScript()`)
// to be able to adapt the checks depending on the request.
if (!($CFG_SANAM["use_public_faq"] && str_ends_with($_GET["_target"], '/front/helpdesk.faq.php'))) {
    Session::checkLoginUser();
}

if (!isset($_GET['_sanam_tab'])) {
    return;
}

if (!isset($_GET['_itemtype']) || empty($_GET['_itemtype'])) {
    return;
}

if (!isset($_GET["sort"])) {
    $_GET["sort"] = "";
}

if (!isset($_GET["order"])) {
    $_GET["order"] = "";
}

if (!isset($_GET["withtemplate"])) {
    $_GET["withtemplate"] = "";
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $_GET['id'] = (int) $_GET['id'];
}

if ($item = getItemForItemtype($_GET['_itemtype'])) {
    if ($item->get_item_to_display_tab) {
        // No id if ruleCollection but check right
        if ($item instanceof RuleCollection) {
            if (!$item->canList()) {
                return;
            }
        } elseif (!isset($_GET["id"]) || $item->isNewID($_GET["id"])) {
            if (!$item::canCreate()) {
                return;
            }
            // This call was initially triggered by a call to `$item->can($ID, CREATE, $input)`
            // but checking the input at display time was blocking legitiame form displays (mainly for `CommonDBRelation`).
            // This call should not be mandatory if `$this->initForm($ID, $options)` was made in every `showForm()`
            // method, but it is not always true, including in plugins, and we cannot refactor this in a bugfixes version.
            // Therefore, the current call prevent introducing `Undefined array key` warning when accessing `$this->fields`.
            // FIXME: Try to move `initForm()` call outside the `showForm()` method in a future SANAM version.
            if ($item instanceof CommonDBTM) {
                $item->initObjectOnCreation($_GET);
            }
        } elseif (!$item->can($_GET["id"], READ)) {
            return;
        }
    }
}

if (isset($_GET['_target'])) {
    $_GET['_target'] = Toolbox::cleanTarget($_GET['_target']);
}

Session::setActiveTab($_GET['_itemtype'], $_GET['_sanam_tab']);

$notvalidoptions = ['_sanam_tab', '_itemtype', 'sort', 'order', 'withtemplate', 'formoptions', 'header_toolbar'];
$options         = $_GET;
foreach ($notvalidoptions as $key) {
    if (isset($options[$key])) {
        unset($options[$key]);
    }
}
if (isset($options['locked'])) {
    ObjectLock::setReadOnlyProfile();
}

Profiler::getInstance()->start('CommonSANAM::displayStandardTab');
CommonSANAM::displayStandardTab($item, $_GET['_sanam_tab'], (int) $_GET["withtemplate"], $options);
Profiler::getInstance()->stop('CommonSANAM::displayStandardTab');
