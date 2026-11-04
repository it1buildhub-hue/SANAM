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

use Sanam\Application\View\TemplateRenderer;

class Item_OperatingSystem extends CommonDBRelation
{
    public static $itemtype_1 = OperatingSystem::class;
    public static $items_id_1 = 'operatingsystems_id';
    public static $itemtype_2 = 'itemtype';
    public static $items_id_2 = 'items_id';
    public static $checkItem_1_Rights = self::DONT_CHECK_ITEM_RIGHTS;

    public static $mustBeAttached_1 = false;


    public static function getTypeName($nb = 0)
    {
        return _n('Item operating system', 'Item operating systems', $nb);
    }


    public function getTabNameForItem(CommonSANAM $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        $nb = 0;
        switch ($item->getType()) {
            default:
                if ($_SESSION['sanamshow_count_on_tabs']) {
                    $nb = self::countForItem($item);
                }
                return self::createTabEntry(OperatingSystem::getTypeName(Session::getPluralNumber()), $nb, $item::getType());
        }
    }

    public static function displayTabContentForItem(CommonSANAM $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        self::showForItem($item, $withtemplate);
        return true;
    }

    /**
     * Get operating systems related to a given item
     *
     * @param CommonDBTM $item  Item instance
     * @param string     $sort  Field to sort on
     * @param string     $order Sort order
     *
     * @return DBmysqlIterator
     */
    public static function getFromItem(CommonDBTM $item, $sort = null, $order = null): DBmysqlIterator
    {
        global $DB;

        if ($sort === null) {
            $sort = "sanam_items_operatingsystems.id";
        }
        if ($order === null) {
            $order = 'ASC';
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'sanam_items_operatingsystems.id AS assocID',
                'sanam_operatingsystems.name',
                'sanam_operatingsystemversions.name AS version',
                'sanam_operatingsystemarchitectures.name AS architecture',
                'sanam_operatingsystemservicepacks.name AS servicepack',
            ],
            'FROM'      => 'sanam_items_operatingsystems',
            'LEFT JOIN' => [
                'sanam_operatingsystems'             => [
                    'ON' => [
                        'sanam_items_operatingsystems' => 'operatingsystems_id',
                        'sanam_operatingsystems'       => 'id',
                    ],
                ],
                'sanam_operatingsystemservicepacks'  => [
                    'ON' => [
                        'sanam_items_operatingsystems'       => 'operatingsystemservicepacks_id',
                        'sanam_operatingsystemservicepacks'  => 'id',
                    ],
                ],
                'sanam_operatingsystemarchitectures' => [
                    'ON' => [
                        'sanam_items_operatingsystems'       => 'operatingsystemarchitectures_id',
                        'sanam_operatingsystemarchitectures' => 'id',
                    ],
                ],
                'sanam_operatingsystemversions'      => [
                    'ON' => [
                        'sanam_items_operatingsystems'    => 'operatingsystemversions_id',
                        'sanam_operatingsystemversions'   => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'sanam_items_operatingsystems.itemtype' => $item->getType(),
                'sanam_items_operatingsystems.items_id' => $item->getID(),
            ],
            'ORDERBY'   => "$sort $order",
        ]);
        return $iterator;
    }

    /**
     * Print the item's operating system form
     *
     * @param CommonDBTM $item Item instance
     * @param int $withtemplate
     *
     * @since 9.2
     *
     * @return void
     **/
    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        global $DB;

        //default options
        $params = ['rand' => mt_rand()];

        $columns = [
            __('Name'),
            _n('Version', 'Versions', 1),
            _n('Architecture', 'Architectures', 1),
            OperatingSystemServicePack::getTypeName(1),
        ];

        if (isset($_GET["order"]) && ($_GET["order"] == "ASC")) {
            $order = "ASC";
        } else {
            $order = "DESC";
        }

        if (
            (isset($_GET["sort"]) && !empty($_GET["sort"]))
            && isset($columns[$_GET["sort"]])
        ) {
            $sort = $_GET["sort"];
        } else {
            $sort = "sanam_items_operatingsystems.id";
        }

        if (empty($withtemplate)) {
            $withtemplate = 0;
        }

        $iterator = self::getFromItem($item, $sort, $order);
        $number = count($iterator);
        $i      = 0;

        $os = [];
        foreach ($iterator as $data) {
            $os[$data['assocID']] = $data;
        }

        $canedit = $item->canEdit($item->getID());

        if ($number <= 1) {
            $id = -1;
            $instance = new self();
            if ($number > 0) {
                $id = array_keys($os)[0];
            } else {
                //set itemtype and items_id
                $instance->fields['itemtype']       = $item->getType();
                $instance->fields['items_id']       = $item->getID();
                $instance->fields['install_date']   = $item->fields['install_date'] ?? '';
                $instance->fields['entities_id']    = $item->fields['entities_id'];
            }
            $instance->showForm($id, [
                'canedit' => $canedit,
                'candel'  => $canedit,
            ]);
            return;
        }

        echo "<div class='spaced'>";
        if (
            $canedit
            && ($withtemplate < 2)
        ) {
            Html::openMassiveActionsForm('mass' . self::class . $params['rand']);
            $massiveactionparams = ['num_displayed'  => min($_SESSION['sanamlist_limit'], $number),
                'container'      => 'mass' . self::class . $params['rand'],
            ];
            Html::showMassiveActions($massiveactionparams);
        }

        echo "<table class='tab_cadre_fixehov'>";

        $header_begin  = "<tr>";
        $header_top    = '';
        $header_bottom = '';
        $header_end    = '';
        if (
            $canedit
            && ($withtemplate < 2)
        ) {
            $header_top    .= "<th width='11'>" . Html::getCheckAllAsCheckbox('mass' . self::class . $params['rand']);
            $header_top    .= "</th>";
            $header_bottom .= "<th width='11'>" . Html::getCheckAllAsCheckbox('mass' . self::class . $params['rand']);
            $header_bottom .= "</th>";
        }

        foreach ($columns as $key => $val) {
            $val = htmlescape($val);
            $header_end .= "<th" . ($sort == $key ? " class='order_$order'" : '') . ">"
                        . "<a href='javascript:reloadTab(\"sort=$key&amp;order="
                          . (($order == "ASC") ? "DESC" : "ASC") . "&amp;start=0\");'>$val</a></th>";
        }

        $header_end .= "</tr>";
        echo $header_begin . $header_top . $header_end;

        foreach ($os as $data) {
            $linkname = $data['name'];
            if ($_SESSION["sanamis_ids_visible"] || empty($data["name"])) {
                $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["assocID"]);
            }
            $link = Toolbox::getItemTypeFormURL(self::getType());
            $name = "<a href=\"" . htmlescape($link) . "?id=" . (int) $data["assocID"] . "\">" . htmlescape($linkname) . "</a>";

            echo "<tr class='tab_bg_1'>";
            if (
                $canedit
                && ($withtemplate < 2)
            ) {
                echo "<td width='10'>";
                Html::showMassiveActionCheckBox(self::class, $data["assocID"]);
                echo "</td>";
            }
            $version = htmlescape($data['version']);
            $architecture = htmlescape($data['architecture']);
            $servicepack = htmlescape($data['servicepack']);
            echo "<td class='center'>{$name}</td>";
            echo "<td class='center'>{$version}</td>";
            echo "<td class='center'>{$architecture}</td>";
            echo "<td class='center'>{$servicepack}</td>";

            echo "</tr>";
            $i++;
        }
        echo $header_begin . $header_bottom . $header_end;

        echo "</table>";
        if ($canedit && ($withtemplate < 2)) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
        }
        echo "</div>";
    }

    public function getConnexityItem(
        $itemtype,
        $items_id,
        $getFromDB = true,
        $getEmpty = true,
        $getFromDBOrEmpty = true
    ) {
        //overrided to set $getFromDBOrEmpty to true
        return parent::getConnexityItem($itemtype, $items_id, $getFromDB, $getEmpty, $getFromDBOrEmpty);
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $this->fields);
        TemplateRenderer::getInstance()->display('pages/assets/operatingsystem.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);

        return true;
    }

    protected function computeFriendlyName()
    {
        $item = getItemForItemtype($this->fields['itemtype']);
        $item->getFromDB($this->fields['items_id']);
        $name = $item->getTypeName(1) . ' ' . $item->getName();

        return $name;
    }

    public function rawSearchOptions()
    {

        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'license_number',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'licenseid',
            'name'               => __('Product ID'),
            'datatype'           => 'string',
            'massiveaction'      => false,
        ];

        return $tab;
    }

    /**
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return array
     */
    public static function rawSearchOptionsToAdd($itemtype)
    {
        $tab = [];
        $tab[] = [
            'id'                => 'operatingsystem',
            'name'              => __('Operating System'),
        ];

        $tab[] = [
            'id'                 => '45',
            'table'              => 'sanam_operatingsystems',
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_items_operatingsystems',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => $itemtype,
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '46',
            'table'              => 'sanam_operatingsystemversions',
            'field'              => 'name',
            'name'               => _n('Version', 'Versions', 1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_items_operatingsystems',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => $itemtype,
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '41',
            'table'              => 'sanam_operatingsystemservicepacks',
            'field'              => 'name',
            'name'               => OperatingSystemServicePack::getTypeName(1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_items_operatingsystems',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => $itemtype,
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '43',
            'table'              => 'sanam_items_operatingsystems',
            'field'              => 'license_number',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
                'specific_itemtype'  => $itemtype,
            ],
        ];

        $tab[] = [
            'id'                 => '44',
            'table'              => 'sanam_items_operatingsystems',
            'field'              => 'licenseid',
            'name'               => __('Product ID'),
            'datatype'           => 'string',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
                'specific_itemtype'  => $itemtype,
            ],
        ];

        $tab[] = [
            'id'                 => '66',
            'table'              => 'sanam_items_operatingsystems',
            'field'              => 'install_date',
            'name'               => __('Installation date'),
            'datatype'           => 'datetime',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'itemtype_item',
                'specific_itemtype'  => $itemtype,
            ],
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => 'sanam_operatingsystemarchitectures',
            'field'              => 'name',
            'name'               => _n('Architecture', 'Architectures', 1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_items_operatingsystems',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => $itemtype,
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '64',
            'table'              => 'sanam_operatingsystemkernels',
            'field'              => 'name',
            'name'               => _n('Kernel', 'Kernels', 1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_operatingsystemkernelversions',
                    'joinparams'         => [
                        'beforejoin'   => [
                            'table'        => 'sanam_items_operatingsystems',
                            'joinparams'   => [
                                'jointype'           => 'itemtype_item',
                                'specific_itemtype'  => $itemtype,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '48',
            'table'              => 'sanam_operatingsystemkernelversions',
            'field'              => 'name',
            'name'               => _n('Kernel version', 'Kernel versions', 1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_items_operatingsystems',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => $itemtype,
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '63',
            'table'              => 'sanam_operatingsystemeditions',
            'field'              => 'name',
            'name'               => _n('Edition', 'Editions', 1),
            'datatype'           => 'dropdown',
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_items_operatingsystems',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => $itemtype,
                    ],
                ],
            ],
        ];

        return $tab;
    }


    public static function getRelationMassiveActionsSpecificities()
    {
        global $CFG_SANAM;

        $specificities              = parent::getRelationMassiveActionsSpecificities();

        $specificities['itemtypes'] = $CFG_SANAM['operatingsystem_types'];
        return $specificities;
    }
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case 'update':
                static::showFormMassiveUpdate($ma);
                return true;
        }

        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @param MassiveAction $ma
     *
     * @return void
     */
    public static function showFormMassiveUpdate($ma)
    {
        global $CFG_SANAM;

        $rand = mt_rand();
        Dropdown::showFromArray(
            'os_field',
            [
                'OperatingSystem'             => __('Name'),
                'OperatingSystemVersion'      => _n('Version', 'Versions', 1),
                'OperatingSystemArchitecture' => _n('Architecture', 'Architectures', 1),
                'OperatingSystemKernel'       => OperatingSystemKernel::getTypeName(1),
                'OperatingSystemKernelVersion' => OperatingSystemKernelVersion::getTypeName(1),
                'OperatingSystemEdition'      => _n('Edition', 'Editions', 1),
            ],
            [
                'display_emptychoice'   => true,
                'rand'                  => $rand,
            ]
        );

        Ajax::updateItemOnSelectEvent(
            "dropdown_os_field$rand",
            "results_os_field$rand",
            $CFG_SANAM["root_doc"]
            . "/ajax/dropdownMassiveActionOs.php",
            [
                'itemtype'  => '__VALUE__',
                'rand'      => $rand,
            ]
        );
        echo "<span id='results_os_field$rand'></span> \n";
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {
            case 'update':
                $input = $ma->getInput();
                unset($input['update']);
                unset($input['os_field']);
                $ios = new Item_OperatingSystem();
                foreach ($ids as $id) {
                    if ($item->getFromDB($id)) {
                        $exists = $ios->getFromDBByCrit([
                            'itemtype'  => $item->getType(),
                            'items_id'  => $item->getID(),
                        ]);

                        $item_input = $exists
                            ? ['id'  => $ios->getID()] + $input
                            : ['itemtype' => $item->getType(), 'items_id' => $item->getID()] + $input;

                        if (
                            ($exists && !$ios->can($ios->getID(), UPDATE, $item_input))
                            || (!$exists && !$ios->can(-1, CREATE, $item_input))
                        ) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            continue;
                        }

                        $ok = false;
                        if ($exists) {
                            $ok = $ios->update($item_input);
                        } else {
                            $ok = $ios->add($item_input);
                        }

                        if ($ok != false) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                    }
                }
                break;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function prepareInputForAdd($input)
    {
        $item = getItemForItemtype($input['itemtype']);
        $item->getFromDB($input['items_id']);
        $input['entities_id'] = $item->fields['entities_id'];
        $input['is_recursive'] = $item->fields['is_recursive'];
        return $input;
    }


    public static function getIcon()
    {
        return OperatingSystem::getIcon();
    }
}
