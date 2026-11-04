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

use Sanam\Asset\Asset_PeripheralAsset;
use Sanam\Features\AssignableItem;
use Sanam\Features\AssignableItemInterface;
use Sanam\Features\Clonable;
use Sanam\Features\DCBreadcrumb;
use Sanam\Features\DCBreadcrumbInterface;
use Sanam\Features\Inventoriable;
use Sanam\Features\StateInterface;
use Sanam\Socket;

/**
 * Peripheral Class
 **/
class Peripheral extends CommonDBTM implements AssignableItemInterface, DCBreadcrumbInterface, StateInterface
{
    use DCBreadcrumb;
    /** @use Clonable<static> */
    use Clonable;
    use Inventoriable;
    use Sanam\Features\State;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
    }

    // From CommonDBTM
    public $dohistory                   = true;

    protected static $forward_entity_to = ['Infocom', 'NetworkPort', 'ReservationItem',
        'Item_OperatingSystem', 'Item_SoftwareVersion',
    ];

    public static $rightname                   = 'peripheral';
    protected $usenotepad               = true;

    public function getCloneRelations(): array
    {
        return [
            Item_OperatingSystem::class,
            Item_Devices::class,
            Infocom::class,
            NetworkPort::class,
            Contract_Item::class,
            Document_Item::class,
            Asset_PeripheralAsset::class,
            KnowbaseItem_Item::class,
            Appliance_Item::class,
            Certificate_Item::class,
            Domain_Item::class,
            Item_Project::class,
            Item_SoftwareLicense::class,
            Item_SoftwareVersion::class,
            ManualLink::class,
            Socket::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Peripheral', 'Peripherals', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets', self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'inventory';
    }

    /**
     * @see CommonDBTM::useDeletedToLockIfDynamic()
     *
     * @since 0.84
     **/
    public function useDeletedToLockIfDynamic()
    {
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addImpactTab($ong, $options);
        $this->addStandardTab(Item_OperatingSystem::class, $ong, $options);
        $this->addStandardTab(Item_SoftwareVersion::class, $ong, $options);
        $this->addStandardTab(Item_Devices::class, $ong, $options);
        $this->addStandardTab(Item_Line::class, $ong, $options);
        $this->addStandardTab(Asset_PeripheralAsset::class, $ong, $options);
        $this->addStandardTab(NetworkPort::class, $ong, $options);
        $this->addStandardTab(Socket::class, $ong, $options);
        $this->addStandardTab(Infocom::class, $ong, $options);
        $this->addStandardTab(Contract_Item::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(KnowbaseItem_Item::class, $ong, $options);
        $this->addStandardTab(Item_Ticket::class, $ong, $options);
        $this->addStandardTab(Item_Problem::class, $ong, $options);
        $this->addStandardTab(Change_Item::class, $ong, $options);
        $this->addStandardTab(Item_Project::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Lock::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Reservation::class, $ong, $options);
        $this->addStandardTab(Certificate_Item::class, $ong, $options);
        $this->addStandardTab(Domain_Item::class, $ong, $options);
        $this->addStandardTab(Appliance_Item::class, $ong, $options);
        $this->addStandardTab(RuleMatchedLog::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }


    public function prepareInputForAdd($input)
    {
        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id']);
        unset($input['withtemplate']);
        $input = $this->prepareInputForAddAssignableItem($input);
        return $input;
    }


    /**
     * Return the linked items (`Asset_PeripheralAsset` relations)
     *
     * @return array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
     * @since 0.84.4
     **/
    public function getLinkedItems()
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'itemtype_asset',
                'items_id_asset',
            ],
            'FROM'   => Asset_PeripheralAsset::getTable(),
            'WHERE'  => [
                'itemtype_peripheral' => $this->getType(),
                'items_id_peripheral' => $this->fields['id'],
            ],
        ]);
        $tab = [];
        foreach ($iterator as $data) {
            $tab[$data['itemtype_asset']][$data['items_id_asset']] = $data['items_id_asset'];
        }
        return $tab;
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);

        if (static::canUpdate()) {
            Asset_PeripheralAsset::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);
            $actions += [
                'Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
               => "<i class='ti ti-key'></i>"
                  . _sx('button', 'Add a license'),
            ];
            KnowbaseItem_Item::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);
        }

        return $actions;
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => 'sanam_peripheraltypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => 'sanam_peripheralmodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => State::getTable(),
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => $this->getStateVisibilityCriteria(),
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'sanam_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'sanam_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'condition'          => ['is_itemgroup' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => $this->getTable(),
            'field'              => 'brand',
            'name'               => __('Brand'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'sanam_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'sanam_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'sanam_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Group in charge'),
            'condition'          => ['is_assign' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'sanam_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '61',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'sanam_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '82',
            'table'              => $this->getTable(),
            'field'              => 'is_global',
            'name'               => __('Global management'),
            'datatype'           => 'bool',
            'massiveaction'      => false,
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Rack::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, PeripheralModel::rawSearchOptionsToAdd());

        $tab = array_merge($tab, DCRoom::rawSearchOptionsToAdd());

        return $tab;
    }

    /**
     * @param ?class-string<CommonDBTM>  $itemtype
     *
     * @return array
     */
    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'peripheral',
            'name'               => self::getTypeName(Session::getPluralNumber()),
        ];

        $tab[] = [
            'id'                 => '1430',
            'table'              => Asset_PeripheralAsset::getTable(),
            'field'              => 'id',
            'name'               => _x('quantity', 'Number of peripherals'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'                  => 'itemtype_item',
                'specific_items_id_column'  => 'items_id_asset',
                'specific_itemtype_column'  => 'itemtype_asset',
                'condition'                 => ['NEWTABLE.' . 'itemtype_peripheral' => 'Peripheral'],
            ],
        ];

        return $tab;
    }

    public static function getIcon()
    {
        return "fab fa-usb";
    }
}
