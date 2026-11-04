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
use Sanam\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Sanam\Socket;

/**
 * Relation constants between tables.
 *
 * This mapping is used for to detect links between objects.
 * For example, it is used to detect if items are associated to another item that
 * is going to be deleted, in order to prevent deletion or ask for user what to do with
 * linked items.
 *
 * Format is:
 * [
 *    'referenced_table_name' => [
 *       'linked_table_name_1' => 'foreign_key_1',
 *       'linked_table_name_2' => ['foreign_key_2', 'foreign_key_3'],
 *       'linked_table_name_2' => [['items_id', 'itemtype']],
 *    ]
 * ]
 * where:
 *  - 'referenced_table_name' is the name of a table having its id referenced in other tables,
 *  - 'linked_table_name_*' is the name of a table that have foreign keys referencing the table 'referenced_table_name',
 *  - 'foreign_key_*' is the name of the field that is a foreign key (can be ['items_id', 'itemtype']).
 *
 * /!\ "_" prefix is used to disable usage check on relations while deleting an item when they are
 *     handled by application.
 *     Applications handle specific usage check and links updates :
 *      - in `CommonDBTM::cleanRelationTable()` method,
 *      - in `$item::cleanDBonPurge()` method,
 *      - using `$forward_entity_to` values,
 *      - by `CommonTreeDropdown` logic for recursive keys.
 *     Relations will still be used to check ability to disable recursivity on an element.
 *
 * /!\ Table's names are in alphabetic order - Please respect it
 *
 * @var array $RELATION
 */
$RELATION = [

    'sanam_agents' => [
        'sanam_refusedequipments' => 'agents_id',
        'sanam_rulematchedlogs'   => 'agents_id',
        'sanam_unmanageds'        => 'agents_id',
    ],

    'sanam_agenttypes' => [
        '_sanam_agents' => 'agenttypes_id',
    ],

    'sanam_applianceenvironments' => [
        'sanam_appliances' => 'applianceenvironments_id',
    ],

    'sanam_appliances'     => [
        '_sanam_appliances_items' => 'appliances_id',
    ],

    'sanam_appliances_items' => [
        '_sanam_appliances_items_relations' => 'appliances_items_id',
    ],

    'sanam_appliancetypes' => [
        'sanam_appliances' => 'appliancetypes_id',
    ],

    'sanam_assets_assetdefinitions' => [
        '_sanam_assets_assets' => 'assets_assetdefinitions_id',
        '_sanam_assets_assetmodels' => 'assets_assetdefinitions_id',
        '_sanam_assets_assettypes' => 'assets_assetdefinitions_id',
        '_sanam_assets_customfielddefinitions' => 'assets_assetdefinitions_id',
    ],

    'sanam_assets_assetmodels' => [
        'sanam_assets_assets' => 'assets_assetmodels_id',
    ],

    'sanam_assets_assettypes' => [
        'sanam_assets_assets' => 'assets_assettypes_id',
    ],

    'sanam_databaseinstancetypes' => [
        'sanam_databaseinstances' => 'databaseinstancetypes_id',
    ],

    'sanam_authldaps' => [
        'sanam_authldapreplicates' => 'authldaps_id',
        'sanam_entities'           => 'authldaps_id',
        'sanam_users'              => 'auths_id',
    ],

    'sanam_authmails' => [
        'sanam_users' => 'auths_id',
    ],

    'sanam_autoupdatesystems' => [
        'sanam_clusters'          => 'autoupdatesystems_id',
        'sanam_computers'         => 'autoupdatesystems_id',
        'sanam_databaseinstances' => 'autoupdatesystems_id',
        'sanam_monitors'          => 'autoupdatesystems_id',
        'sanam_networkequipments' => 'autoupdatesystems_id',
        'sanam_peripherals'       => 'autoupdatesystems_id',
        'sanam_phones'            => 'autoupdatesystems_id',
        'sanam_printers'          => 'autoupdatesystems_id',
        'sanam_refusedequipments' => 'autoupdatesystems_id',
        'sanam_unmanageds'        => 'autoupdatesystems_id',
        'sanam_assets_assets'     => 'autoupdatesystems_id',
    ],

    'sanam_budgets' => [
        'sanam_changecosts'   => 'budgets_id',
        'sanam_contractcosts' => 'budgets_id',
        'sanam_infocoms'      => 'budgets_id',
        'sanam_problemcosts'  => 'budgets_id',
        'sanam_projectcosts'  => 'budgets_id',
        'sanam_ticketcosts'   => 'budgets_id',
    ],

    'sanam_budgettypes' => [
        'sanam_budgets' => 'budgettypes_id',
    ],

    'sanam_businesscriticities' => [
        '_sanam_businesscriticities' => 'businesscriticities_id',
        'sanam_infocoms'             => 'businesscriticities_id',
    ],

    'sanam_cablestrands' => [
        'sanam_cables' => 'cablestrands_id',
    ],

    'sanam_cabletypes' => [
        'sanam_cables' => 'cabletypes_id',
    ],

    'sanam_calendars' => [
        '_sanam_calendars_holidays' => 'calendars_id',
        '_sanam_calendarsegments'   => 'calendars_id',
        'sanam_entities'            => 'calendars_id',
        'sanam_olas'                => 'calendars_id',
        'sanam_slas'                => 'calendars_id',
        'sanam_slms'                => 'calendars_id',
        'sanam_recurrentchanges'    => 'calendars_id',
        'sanam_ticketrecurrents'    => 'calendars_id',
        'sanam_pendingreasons'     => 'calendars_id',
    ],

    'sanam_cartridgeitems' => [
        '_sanam_cartridgeitems_printermodels' => 'cartridgeitems_id',
        '_sanam_cartridges'                   => 'cartridgeitems_id',
    ],

    'sanam_cartridgeitemtypes' => [
        'sanam_cartridgeitems' => 'cartridgeitemtypes_id',
    ],

    'sanam_certificates' => [
        '_sanam_certificates_items' => 'certificates_id',
    ],

    'sanam_certificatetypes' => [
        'sanam_certificates' => 'certificatetypes_id',
    ],

    'sanam_changes' => [
        '_sanam_changecosts'           => 'changes_id',
        '_sanam_changes_changes'       => [
            'changes_id_1',
            'changes_id_2',
        ],
        '_sanam_changes_groups'        => 'changes_id',
        '_sanam_changes_items'         => 'changes_id',
        '_sanam_changes_problems'      => 'changes_id',
        '_sanam_changes_suppliers'     => 'changes_id',
        '_sanam_changes_tickets'       => 'changes_id',
        '_sanam_changes_users'         => 'changes_id',
        '_sanam_changesatisfactions'   => 'changes_id',
        '_sanam_changetasks'           => 'changes_id',
        '_sanam_changevalidations'     => 'changes_id',
        '_sanam_itils_projects'        => [['items_id', 'itemtype']],
        '_sanam_itilfollowups'         => [['items_id', 'itemtype']],
        '_sanam_itils_validationsteps' => [['items_id', 'itemtype']],
        '_sanam_itilsolutions'         => [['items_id', 'itemtype']],
    ],

    'sanam_changetemplates' => [
        'sanam_entities'                        => 'changetemplates_id',
        'sanam_itilcategories'                  => [
            'changetemplates_id',
        ],
        'sanam_changes'                         => 'changetemplates_id',
        '_sanam_changetemplatehiddenfields'     => 'changetemplates_id',
        '_sanam_changetemplatemandatoryfields'  => 'changetemplates_id',
        '_sanam_changetemplatepredefinedfields' => 'changetemplates_id',
        '_sanam_changetemplatereadonlyfields'   => 'changetemplates_id',
        'sanam_profiles'                        => 'changetemplates_id',
        'sanam_recurrentchanges'                => 'changetemplates_id',
    ],

    'sanam_clusters' => [
        '_sanam_items_clusters' => 'clusters_id',
    ],

    'sanam_clustertypes' => [
        'sanam_clusters' => 'clustertypes_id',
    ],

    'sanam_computermodels' => [
        'sanam_computers' => 'computermodels_id',
    ],

    'sanam_computers' => [
        'sanam_networknames' => [['items_id', 'itemtype']], // FIXME Find a list that can be used to declare this polymorphic relation
    ],

    'sanam_computertypes' => [
        'sanam_computers' => 'computertypes_id',
    ],

    'sanam_consumableitems' => [
        '_sanam_consumables' => 'consumableitems_id',
    ],

    'sanam_consumableitemtypes' => [
        'sanam_consumableitems' => 'consumableitemtypes_id',
    ],

    'sanam_contacts' => [
        '_sanam_contacts_suppliers' => 'contacts_id',
    ],

    'sanam_contacttypes' => [
        'sanam_contacts' => 'contacttypes_id',
    ],

    'sanam_contracts' => [
        '_sanam_contractcosts'       => 'contracts_id',
        '_sanam_contracts_items'     => 'contracts_id',
        '_sanam_contracts_suppliers' => 'contracts_id',
        'sanam_entities'             => 'contracts_id_default',
        '_sanam_tickets_contracts'   => 'contracts_id',
        '_sanam_contracts_users'    => 'contracts_id',
    ],

    'sanam_contracttypes' => [
        'sanam_contracts' => 'contracttypes_id',
    ],

    'sanam_crontasklogs' => [
        '_sanam_crontasklogs' => 'crontasklogs_id',
    ],

    'sanam_crontasks' => [
        '_sanam_crontasklogs' => 'crontasks_id',
    ],

    'sanam_dashboards_dashboards' => [
        '_sanam_dashboards_filters' => 'dashboards_dashboards_id',
        '_sanam_dashboards_items'   => 'dashboards_dashboards_id',
        '_sanam_dashboards_rights'  => 'dashboards_dashboards_id',
    ],

    'sanam_databaseinstancecategories' => [
        'sanam_databaseinstances' => 'databaseinstancecategories_id',
    ],

    'sanam_databaseinstances' => [
        'sanam_databases' => 'databaseinstances_id',
    ],

    'sanam_datacenters' => [
        'sanam_dcrooms' => 'datacenters_id',
    ],

    'sanam_dcrooms' => [
        'sanam_racks' => 'dcrooms_id',
    ],

    'sanam_devicebatterymodels' => [
        'sanam_devicebatteries' => 'devicebatterymodels_id',
    ],

    'sanam_devicecameramodels' => [
        'sanam_devicecameras' => 'devicecameramodels_id',
    ],

    'sanam_devicebatterytypes' => [
        'sanam_devicebatteries' => 'devicebatterytypes_id',
    ],

    'sanam_devicecasemodels' => [
        'sanam_devicecases' => 'devicecasemodels_id',
    ],

    'sanam_devicecasetypes' => [
        'sanam_devicecases' => 'devicecasetypes_id',
    ],

    'sanam_devicecontrolmodels' => [
        'sanam_devicecontrols' => 'devicecontrolmodels_id',
    ],

    'sanam_devicedrivemodels' => [
        'sanam_devicedrives' => 'devicedrivemodels_id',
    ],

    'sanam_devicefirmwaremodels' => [
        'sanam_devicefirmwares' => 'devicefirmwaremodels_id',
    ],

    'sanam_devicefirmwaretypes' => [
        'sanam_devicefirmwares' => 'devicefirmwaretypes_id',
    ],

    'sanam_devicegenericmodels' => [
        'sanam_devicegenerics' => 'devicegenericmodels_id',
    ],

    'sanam_devicegenerictypes' => [
        'sanam_devicegenerics' => 'devicegenerictypes_id',
    ],

    'sanam_devicegraphiccardmodels' => [
        'sanam_devicegraphiccards' => 'devicegraphiccardmodels_id',
    ],

    'sanam_deviceharddrivemodels' => [
        'sanam_deviceharddrives' => 'deviceharddrivemodels_id',
    ],

    'sanam_deviceharddrivetypes' => [
        'sanam_deviceharddrives' => 'deviceharddrivetypes_id',
    ],

    'sanam_devicememorymodels' => [
        'sanam_devicememories' => 'devicememorymodels_id',
    ],

    'sanam_devicememorytypes' => [
        'sanam_devicememories' => 'devicememorytypes_id',
    ],

    'sanam_devicemotherboardmodels' => [
        'sanam_devicemotherboards' => 'devicemotherboardmodels_id',
    ],

    'sanam_devicenetworkcardmodels' => [
        'sanam_devicenetworkcards' => 'devicenetworkcardmodels_id',
        'sanam_devicepcis'         => 'devicenetworkcardmodels_id', // FIXME This field should probably removed
    ],

    'sanam_devicepcimodels' => [
        'sanam_devicepcis' => 'devicepcimodels_id',
    ],

    'sanam_devicepowersupplymodels' => [
        'sanam_devicepowersupplies' => 'devicepowersupplymodels_id',
    ],

    'sanam_deviceprocessormodels' => [
        'sanam_deviceprocessors' => 'deviceprocessormodels_id',
    ],

    'sanam_devicesensormodels' => [
        'sanam_devicesensors' => 'devicesensormodels_id',
    ],

    'sanam_devicesensortypes' => [
        'sanam_devicesensors' => 'devicesensortypes_id',
    ],

    'sanam_devicesimcardtypes' => [
        'sanam_devicesimcards' => 'devicesimcardtypes_id',
    ],

    'sanam_devicesoundcardmodels' => [
        'sanam_devicesoundcards' => 'devicesoundcardmodels_id',
    ],

    'sanam_documentcategories' => [
        'sanam_documentcategories' => 'documentcategories_id',
        'sanam_documents'          => 'documentcategories_id',
    ],

    'sanam_documents' => [
        '_sanam_documents_items' => 'documents_id',
    ],

    'sanam_domainrelations' => [
        'sanam_domains_items' => 'domainrelations_id',
    ],

    'sanam_domains'    => [
        '_sanam_domainrecords' => 'domains_id',
        '_sanam_domains_items' => 'domains_id',
    ],

    'sanam_domaintypes' => [
        'sanam_domains'  => 'domaintypes_id',
    ],

    'sanam_domainrecordtypes'    => [
        'sanam_domainrecords'  => 'domainrecordtypes_id',
    ],

    'sanam_dropdowns_dropdowndefinitions' => [
        '_sanam_dropdowns_dropdowns' => 'dropdowns_dropdowndefinitions_id',
    ],

    'sanam_dropdowns_dropdowns' => [
        'sanam_dropdowns_dropdowns' => 'dropdowns_dropdowns_id',
    ],

    'sanam_enclosuremodels' => [
        'sanam_enclosures' => 'enclosuremodels_id',
    ],

    'sanam_enclosures' => [
        '_sanam_items_enclosures' => 'enclosures_id',
    ],

    'sanam_entities' => [
        'sanam_agents'                      => 'entities_id',
        'sanam_apiclients'                  => 'entities_id',
        'sanam_appliances'                  => 'entities_id',
        'sanam_appliancetypes'              => 'entities_id',
        'sanam_assets_assets'               => 'entities_id',
        'sanam_budgets'                     => 'entities_id',
        'sanam_businesscriticities'         => 'entities_id',
        'sanam_cables'                      => 'entities_id',
        'sanam_calendars'                   => 'entities_id',
        '_sanam_calendarsegments'           => 'entities_id',
        'sanam_cartridgeitems'              => 'entities_id',
        '_sanam_cartridges'                 => 'entities_id',
        'sanam_certificates'                => 'entities_id',
        'sanam_certificatetypes'            => 'entities_id',
        '_sanam_changecosts'                => 'entities_id',
        'sanam_changes'                     => 'entities_id',
        'sanam_changetemplates'             => 'entities_id',
        '_sanam_changevalidations'          => 'entities_id',
        'sanam_clusters'                    => 'entities_id',
        'sanam_clustertypes'                => 'entities_id',
        'sanam_computers'                   => 'entities_id',
        'sanam_dropdowns_dropdowns'         => 'entities_id',
        'sanam_consumableitems'             => 'entities_id',
        '_sanam_consumables'                => 'entities_id',
        'sanam_contacts'                    => 'entities_id',
        '_sanam_contractcosts'              => 'entities_id',
        'sanam_contracts'                   => 'entities_id',
        'sanam_databaseinstances'           => 'entities_id',
        '_sanam_databases'                  => 'entities_id', // forwarded by Database
        'sanam_datacenters'                 => 'entities_id',
        'sanam_dcrooms'                     => 'entities_id',
        'sanam_devicebatteries'             => 'entities_id',
        'sanam_devicecameras'               => 'entities_id',
        'sanam_devicecases'                 => 'entities_id',
        'sanam_devicecontrols'              => 'entities_id',
        'sanam_devicedrives'                => 'entities_id',
        'sanam_devicefirmwares'             => 'entities_id',
        'sanam_devicegenerics'              => 'entities_id',
        'sanam_devicegraphiccards'          => 'entities_id',
        'sanam_deviceharddrives'            => 'entities_id',
        'sanam_devicememories'              => 'entities_id',
        'sanam_devicemotherboards'          => 'entities_id',
        'sanam_devicenetworkcards'          => 'entities_id',
        'sanam_devicepcis'                  => 'entities_id',
        'sanam_devicepowersupplies'         => 'entities_id',
        'sanam_deviceprocessors'            => 'entities_id',
        'sanam_devicesensors'               => 'entities_id',
        'sanam_devicesimcards'              => 'entities_id',
        'sanam_devicesoundcards'            => 'entities_id',
        'sanam_documents'                   => 'entities_id',
        '_sanam_documents_items'            => 'entities_id',
        'sanam_domainrelations'             => 'entities_id',
        'sanam_domainrecords'               => 'entities_id',
        'sanam_domainrecordtypes'           => 'entities_id',
        'sanam_domains'                     => 'entities_id',
        'sanam_domaintypes'                 => 'entities_id',
        'sanam_enclosures'                  => 'entities_id',
        '_sanam_entities'                   => 'entities_id',
        'sanam_entities'                    => 'entities_id_software',
        '_sanam_entities_knowbaseitems'     => 'entities_id',
        '_sanam_entities_reminders'         => 'entities_id',
        '_sanam_entities_rssfeeds'          => 'entities_id',
        'sanam_fieldblacklists'             => 'entities_id',
        'sanam_fieldunicities'              => 'entities_id',
        'sanam_forms_forms'                 => 'entities_id',
        'sanam_forms_answerssets'           => 'entities_id',
        'sanam_fqdns'                       => 'entities_id',
        'sanam_groups'                      => 'entities_id',
        'sanam_groups_knowbaseitems'        => 'entities_id',
        'sanam_groups_reminders'            => 'entities_id',
        'sanam_groups_rssfeeds'             => 'entities_id',
        'sanam_holidays'                    => 'entities_id',
        'sanam_imageformats'                => 'entities_id',
        'sanam_imageresolutions'            => 'entities_id',
        '_sanam_infocoms'                   => 'entities_id',
        'sanam_ipaddresses'                 => 'entities_id',
        'sanam_ipnetworks'                  => 'entities_id',
        '_sanam_items_devicebatteries'      => 'entities_id',
        '_sanam_items_devicecases'          => 'entities_id',
        '_sanam_items_devicecameras'        => 'entities_id', // forwarded by DeviceCamera
        '_sanam_items_devicecontrols'       => 'entities_id',
        '_sanam_items_devicedrives'         => 'entities_id',
        '_sanam_items_devicefirmwares'      => 'entities_id',
        '_sanam_items_devicegenerics'       => 'entities_id',
        '_sanam_items_devicegraphiccards'   => 'entities_id',
        '_sanam_items_deviceharddrives'     => 'entities_id',
        '_sanam_items_devicememories'       => 'entities_id',
        '_sanam_items_devicemotherboards'   => 'entities_id',
        '_sanam_items_devicenetworkcards'   => 'entities_id',
        '_sanam_items_devicepcis'           => 'entities_id',
        '_sanam_items_devicepowersupplies'  => 'entities_id',
        '_sanam_items_deviceprocessors'     => 'entities_id',
        '_sanam_items_devicesensors'        => 'entities_id',
        '_sanam_items_devicesimcards'       => 'entities_id',
        '_sanam_items_devicesoundcards'     => 'entities_id',
        '_sanam_items_disks'                => 'entities_id',
        '_sanam_items_operatingsystems'     => 'entities_id',
        '_sanam_items_softwareversions'     => 'entities_id',
        '_sanam_itemvirtualmachines'        => 'entities_id',
        'sanam_itilcategories'              => 'entities_id',
        'sanam_itilfollowuptemplates'       => 'entities_id',
        'sanam_itilvalidationtemplates'     => 'entities_id',
        'sanam_knowbaseitemcategories'      => 'entities_id',
        'sanam_knowbaseitems'               => 'entities_id',
        'sanam_knowbaseitems_profiles'      => 'entities_id',
        'sanam_lineoperators'               => 'entities_id',
        'sanam_lines'                       => 'entities_id',
        'sanam_links'                       => 'entities_id',
        'sanam_locations'                   => 'entities_id',
        'sanam_monitors'                    => 'entities_id',
        '_sanam_networkaliases'             => 'entities_id',
        'sanam_networkequipments'           => 'entities_id',
        'sanam_networknames'                => 'entities_id',
        '_sanam_networkports'               => 'entities_id',
        'sanam_networkporttypes'            => 'entities_id',
        'sanam_notifications'               => 'entities_id',
        '_sanam_olalevels'                  => 'entities_id',
        '_sanam_olas'                       => 'entities_id',
        'sanam_passivedcequipments'         => 'entities_id',
        'sanam_pcivendors'                  => 'entities_id',
        'sanam_pdus'                        => 'entities_id',
        'sanam_pdutypes'                    => 'entities_id',
        'sanam_pendingreasons'              => 'entities_id',
        'sanam_peripherals'                 => 'entities_id',
        'sanam_phones'                      => 'entities_id',
        'sanam_planningexternalevents'      => 'entities_id',
        'sanam_planningexternaleventtemplates' => 'entities_id',
        'sanam_printers'                    => 'entities_id',
        '_sanam_problemcosts'               => 'entities_id',
        'sanam_problems'                    => 'entities_id',
        'sanam_problemtemplates'            => 'entities_id',
        'sanam_profiles_reminders'          => 'entities_id',
        'sanam_profiles_rssfeeds'           => 'entities_id',
        '_sanam_profiles_users'             => 'entities_id',
        '_sanam_projectcosts'               => 'entities_id',
        'sanam_projects'                    => 'entities_id',
        '_sanam_projecttasks'               => 'entities_id',
        'sanam_projecttasktemplates'        => 'entities_id',
        'sanam_queuednotifications'         => 'entities_id',
        'sanam_racks'                       => 'entities_id',
        'sanam_racktypes'                   => 'entities_id',
        'sanam_recurrentchanges'            => 'entities_id',
        'sanam_refusedequipments'           => 'entities_id',
        '_sanam_reservationitems'           => 'entities_id',
        'sanam_rules'                       => 'entities_id',
        'sanam_savedsearches'               => 'entities_id',
        '_sanam_slalevels'                  => 'entities_id',
        '_sanam_slas'                       => 'entities_id',
        'sanam_slms'                        => 'entities_id',
        'sanam_softwarelicenses'            => 'entities_id',
        'sanam_softwarelicensetypes'        => 'entities_id',
        'sanam_softwares'                   => 'entities_id',
        '_sanam_softwareversions'           => 'entities_id',
        'sanam_solutiontemplates'           => 'entities_id',
        'sanam_solutiontypes'               => 'entities_id',
        'sanam_states'                      => 'entities_id',
        'sanam_suppliers'                   => 'entities_id',
        'sanam_taskcategories'              => 'entities_id',
        'sanam_tasktemplates'               => 'entities_id',
        '_sanam_ticketcosts'                => 'entities_id',
        'sanam_ticketrecurrents'            => 'entities_id',
        'sanam_tickets'                     => 'entities_id',
        'sanam_tickettemplates'             => 'entities_id',
        '_sanam_ticketvalidations'          => 'entities_id',
        'sanam_unmanageds'                  => 'entities_id',
        'sanam_usbvendors'                  => 'entities_id',
        'sanam_users'                       => 'entities_id',
        'sanam_vlans'                       => 'entities_id',
        'sanam_wifinetworks'                => 'entities_id',
        'sanam_webhooks'                    => 'entities_id',
        'sanam_queuedwebhooks'              => 'entities_id',
    ],

    'sanam_filesystems' => [
        'sanam_items_disks' => 'filesystems_id',
    ],

    'sanam_forms_answerssets' => [
        "_sanam_forms_destinations_answerssets_formdestinationitems" => "forms_answerssets_id",
    ],

    'sanam_forms_categories' => [
        'sanam_forms_categories' => 'forms_categories_id',
        'sanam_forms_forms' => 'forms_categories_id',
        'sanam_knowbaseitems' => 'forms_categories_id',
    ],

    'sanam_forms_forms' => [
        "_sanam_forms_accesscontrols_formaccesscontrols" => "forms_forms_id",
        "_sanam_forms_answerssets"                       => "forms_forms_id",
        "_sanam_forms_destinations_formdestinations"     => "forms_forms_id",
        "_sanam_forms_sections"                          => "forms_forms_id",
        "_sanam_helpdesks_tiles_formtiles"               => "forms_forms_id",
    ],

    'sanam_forms_sections' => [
        "_sanam_forms_questions" => "forms_sections_id",
        "_sanam_forms_comments" => "forms_sections_id",
    ],

    'sanam_fqdns' => [
        'sanam_networkaliases' => 'fqdns_id',
        'sanam_networknames'   => 'fqdns_id',
    ],

    'sanam_groups' => [
        '_sanam_changes_groups'       => 'groups_id',
        'sanam_changetasks'           => 'groups_id_tech',
        'sanam_groups'                => 'groups_id',
        '_sanam_groups_items'         => 'groups_id',
        '_sanam_groups_knowbaseitems' => 'groups_id',
        '_sanam_groups_problems'      => 'groups_id',
        '_sanam_groups_reminders'     => 'groups_id',
        '_sanam_groups_rssfeeds'      => 'groups_id',
        '_sanam_groups_tickets'       => 'groups_id',
        '_sanam_groups_users'         => 'groups_id',
        'sanam_itilcategories'        => 'groups_id',
        'sanam_planningexternalevents' => 'groups_id',
        'sanam_problemtasks'           => 'groups_id_tech',
        'sanam_projects'               => 'groups_id',
        'sanam_tasktemplates'          => 'groups_id_tech',
        'sanam_tickettasks'            => 'groups_id_tech',
        'sanam_users'                  => 'groups_id',
        'sanam_itilvalidationtemplates_targets' => 'groups_id',
    ],

    'sanam_holidays' => [
        '_sanam_calendars_holidays' => 'holidays_id',
    ],

    'sanam_imageformats' => [
        '_sanam_items_devicecameras_imageformats' => 'imageformats_id',
    ],

    'sanam_imageresolutions' => [
        '_sanam_items_devicecameras_imageresolutions' => 'imageresolutions_id',
    ],

    'sanam_impactcontexts' => [
        'sanam_impactitems' => 'impactcontexts_id',
    ],

    'sanam_interfacetypes' => [
        'sanam_devicecontrols'     => 'interfacetypes_id',
        'sanam_devicedrives'       => 'interfacetypes_id',
        'sanam_devicegraphiccards' => 'interfacetypes_id',
        'sanam_deviceharddrives'   => 'interfacetypes_id',
    ],

    'sanam_ipaddresses' => [
        '_sanam_ipaddresses_ipnetworks' => 'ipaddresses_id',
    ],

    'sanam_ipnetworks' => [
        '_sanam_ipaddresses_ipnetworks' => 'ipnetworks_id',
        'sanam_networknames'            => 'ipnetworks_id',
        'sanam_ipnetworks'              => 'ipnetworks_id',
        '_sanam_ipnetworks_vlans'       => 'ipnetworks_id',
    ],

    'sanam_items_devicecameras' => [
        '_sanam_items_devicecameras_imageformats' => 'items_devicecameras_id',
        '_sanam_items_devicecameras_imageresolutions' => 'items_devicecameras_id',
    ],

    'sanam_items_devicenetworkcards' => [
        'sanam_networkportethernets'     => 'items_devicenetworkcards_id',
        'sanam_networkportfiberchannels' => 'items_devicenetworkcards_id',
        'sanam_networkportwifis'         => 'items_devicenetworkcards_id',
    ],

    'sanam_itilcategories' => [
        'sanam_changes'        => 'itilcategories_id',
        'sanam_itilcategories' => 'itilcategories_id',
        'sanam_problems'       => 'itilcategories_id',
        'sanam_tickets'        => 'itilcategories_id',
    ],

    'sanam_itilfollowups' => [
        'sanam_itilsolutions' => 'itilfollowups_id',
    ],

    'sanam_itilfollowuptemplates' => [
        'sanam_pendingreasons' => 'itilfollowuptemplates_id',
    ],

    'sanam_itilvalidationtemplates' => [
        '_sanam_itilvalidationtemplates_targets' => 'itilvalidationtemplates_id',
        'sanam_changevalidations' => 'itilvalidationtemplates_id',
        'sanam_ticketvalidations' => 'itilvalidationtemplates_id',
    ],

    'sanam_itils_validationsteps' => [
        'sanam_ticketvalidations' => 'itils_validationsteps_id',
        'sanam_changevalidations' => 'itils_validationsteps_id',
    ],

    'sanam_knowbaseitemcategories' => [
        'sanam_itilcategories'            => 'knowbaseitemcategories_id',
        'sanam_knowbaseitemcategories'    => 'knowbaseitemcategories_id',
        '_sanam_knowbaseitems_knowbaseitemcategories' => 'knowbaseitemcategories_id',
        'sanam_taskcategories'            => 'knowbaseitemcategories_id',
    ],

    'sanam_knowbaseitems' => [
        '_sanam_entities_knowbaseitems'   => 'knowbaseitems_id',
        '_sanam_groups_knowbaseitems'     => 'knowbaseitems_id',
        '_sanam_knowbaseitems_comments'   => 'knowbaseitems_id',
        '_sanam_knowbaseitems_items'      => 'knowbaseitems_id',
        '_sanam_knowbaseitems_profiles'   => 'knowbaseitems_id',
        '_sanam_knowbaseitems_revisions'  => 'knowbaseitems_id',
        '_sanam_knowbaseitems_users'      => 'knowbaseitems_id',
        '_sanam_knowbaseitemtranslations' => 'knowbaseitems_id',
        '_sanam_knowbaseitems_knowbaseitemcategories' => 'knowbaseitems_id',
    ],

    'sanam_knowbaseitems_comments' => [
        'sanam_knowbaseitems_comments' => 'parent_comment_id',
    ],

    'sanam_lineoperators' => [
        'sanam_lines' => 'lineoperators_id',
    ],

    'sanam_lines' => [
        'sanam_items_devicesimcards' => 'lines_id',
        '_sanam_items_lines' => 'lines_id',
    ],

    'sanam_linetypes' => [
        'sanam_lines' => 'linetypes_id',
    ],

    'sanam_links' => [
        '_sanam_links_itemtypes' => 'links_id',
    ],

    'sanam_locations' => [
        'sanam_appliances'                => 'locations_id',
        'sanam_assets_assets'             => 'locations_id',
        'sanam_budgets'                   => 'locations_id',
        'sanam_cartridgeitems'            => 'locations_id',
        'sanam_certificates'              => 'locations_id',
        'sanam_changes'                   => 'locations_id',
        'sanam_computers'                 => 'locations_id',
        'sanam_contracts'                 => 'locations_id',
        'sanam_consumableitems'           => 'locations_id',
        'sanam_databaseinstances'         => 'locations_id',
        'sanam_datacenters'               => 'locations_id',
        'sanam_dcrooms'                   => 'locations_id',
        'sanam_devicegenerics'            => 'locations_id',
        'sanam_devicesensors'             => 'locations_id',
        'sanam_enclosures'                => 'locations_id',
        'sanam_items_devicebatteries'     => 'locations_id',
        'sanam_items_devicecameras'       => 'locations_id',
        'sanam_items_devicecases'         => 'locations_id',
        'sanam_items_devicecontrols'      => 'locations_id',
        'sanam_items_devicedrives'        => 'locations_id',
        'sanam_items_devicefirmwares'     => 'locations_id',
        'sanam_items_devicegenerics'      => 'locations_id',
        'sanam_items_devicegraphiccards'  => 'locations_id',
        'sanam_items_deviceharddrives'    => 'locations_id',
        'sanam_items_devicememories'      => 'locations_id',
        'sanam_items_devicemotherboards'  => 'locations_id',
        'sanam_items_devicenetworkcards'  => 'locations_id',
        'sanam_items_devicepcis'          => 'locations_id',
        'sanam_items_devicepowersupplies' => 'locations_id',
        'sanam_items_deviceprocessors'    => 'locations_id',
        'sanam_items_devicesensors'       => 'locations_id',
        'sanam_items_devicesimcards'      => 'locations_id',
        'sanam_items_devicesoundcards'    => 'locations_id',
        'sanam_lines'                     => 'locations_id',
        'sanam_locations'                 => 'locations_id',
        'sanam_monitors'                  => 'locations_id',
        'sanam_networkequipments'         => 'locations_id',
        'sanam_passivedcequipments'       => 'locations_id',
        'sanam_pdus'                      => 'locations_id',
        'sanam_peripherals'               => 'locations_id',
        'sanam_phones'                    => 'locations_id',
        'sanam_printers'                  => 'locations_id',
        'sanam_problems'                  => 'locations_id',
        'sanam_racks'                     => 'locations_id',
        'sanam_sockets'                   => 'locations_id',
        'sanam_softwarelicenses'          => 'locations_id',
        'sanam_softwares'                 => 'locations_id',
        'sanam_tickets'                   => 'locations_id',
        'sanam_unmanageds'                => 'locations_id',
        'sanam_users'                     => 'locations_id',
    ],

    'sanam_mailcollectors' => [
        'sanam_notimportedemails' => 'mailcollectors_id',
    ],

    'sanam_manufacturers' => [
        'sanam_appliances'          => 'manufacturers_id',
        'sanam_assets_assets'       => 'manufacturers_id',
        'sanam_cartridgeitems'      => 'manufacturers_id',
        'sanam_certificates'        => 'manufacturers_id',
        'sanam_itemantiviruses'    => 'manufacturers_id',
        'sanam_computers'           => 'manufacturers_id',
        'sanam_consumableitems'     => 'manufacturers_id',
        'sanam_databaseinstances'   => 'manufacturers_id',
        'sanam_devicebatteries'     => 'manufacturers_id',
        'sanam_devicecameras'       => 'manufacturers_id',
        'sanam_devicecases'         => 'manufacturers_id',
        'sanam_devicecontrols'      => 'manufacturers_id',
        'sanam_devicedrives'        => 'manufacturers_id',
        'sanam_devicefirmwares'     => 'manufacturers_id',
        'sanam_devicegenerics'      => 'manufacturers_id',
        'sanam_devicegraphiccards'  => 'manufacturers_id',
        'sanam_deviceharddrives'    => 'manufacturers_id',
        'sanam_devicememories'      => 'manufacturers_id',
        'sanam_devicemotherboards'  => 'manufacturers_id',
        'sanam_devicenetworkcards'  => 'manufacturers_id',
        'sanam_devicepcis'          => 'manufacturers_id',
        'sanam_devicepowersupplies' => 'manufacturers_id',
        'sanam_deviceprocessors'    => 'manufacturers_id',
        'sanam_devicesensors'       => 'manufacturers_id',
        'sanam_devicesimcards'      => 'manufacturers_id',
        'sanam_devicesoundcards'    => 'manufacturers_id',
        'sanam_enclosures'          => 'manufacturers_id',
        'sanam_monitors'            => 'manufacturers_id',
        'sanam_networkequipments'   => 'manufacturers_id',
        'sanam_passivedcequipments' => 'manufacturers_id',
        'sanam_pdus'                => 'manufacturers_id',
        'sanam_peripherals'         => 'manufacturers_id',
        'sanam_phones'              => 'manufacturers_id',
        'sanam_printers'            => 'manufacturers_id',
        'sanam_racks'               => 'manufacturers_id',
        'sanam_softwarelicenses'    => 'manufacturers_id',
        'sanam_softwares'           => 'manufacturers_id',
        'sanam_unmanageds'          => 'manufacturers_id',
    ],

    'sanam_monitormodels' => [
        'sanam_monitors' => 'monitormodels_id',
    ],

    'sanam_monitortypes' => [
        'sanam_monitors' => 'monitortypes_id',
    ],

    'sanam_networkequipmentmodels' => [
        'sanam_networkequipments' => 'networkequipmentmodels_id',
    ],

    'sanam_networkequipmenttypes' => [
        'sanam_networkequipments' => 'networkequipmenttypes_id',
    ],

    'sanam_networknames' => [
        '_sanam_networkaliases' => 'networknames_id',
    ],

    'sanam_networkportfiberchanneltypes' => [
        'sanam_networkportfiberchannels' => 'networkportfiberchanneltypes_id',
    ],

    'sanam_networkports' => [
        '_sanam_networkportaggregates'     => 'networkports_id',
        '_sanam_networkportaliases'        => 'networkports_id',
        'sanam_networkportaliases'         => 'networkports_id_alias',
        '_sanam_networkportconnectionlogs'  => [
            'networkports_id_destination',
            'networkports_id_source',
        ],
        '_sanam_networkportdialups'        => 'networkports_id',
        '_sanam_networkportethernets'      => 'networkports_id',
        '_sanam_networkportfiberchannels'  => 'networkports_id',
        '_sanam_networkportlocals'         => 'networkports_id',
        '_sanam_networkportmetrics'        => 'networkports_id',
        '_sanam_networkports_networkports' => [
            'networkports_id_1',
            'networkports_id_2',
        ],
        '_sanam_networkports_vlans'        => 'networkports_id',
        '_sanam_networkportwifis'          => 'networkports_id',
        'sanam_sockets'                    => 'networkports_id',
    ],

    'sanam_networkportwifis' => [
        'sanam_networkportwifis' => 'networkportwifis_id',
    ],

    'sanam_networks' => [
        'sanam_computers'         => 'networks_id',
        'sanam_networkequipments' => 'networks_id',
        'sanam_printers'          => 'networks_id',
        'sanam_unmanageds'        => 'networks_id',
    ],

    'sanam_notifications' => [
        '_sanam_notifications_notificationtemplates' => 'notifications_id',
        '_sanam_notificationtargets'                 => 'notifications_id',
    ],

    'sanam_notificationtemplates' => [
        '_sanam_notifications_notificationtemplates' => 'notificationtemplates_id',
        '_sanam_notificationtemplatetranslations'    => 'notificationtemplates_id',
        '_sanam_queuednotifications'                 => 'notificationtemplates_id',
    ],

    'sanam_olalevels' => [
        '_sanam_olalevelactions'   => 'olalevels_id',
        '_sanam_olalevelcriterias' => 'olalevels_id',
        '_sanam_olalevels_tickets' => 'olalevels_id',
        'sanam_tickets'            => 'olalevels_id_ttr',
    ],

    'sanam_olas' => [
        'sanam_olalevels' => 'olas_id',
        'sanam_tickets'   => [
            'olas_id_ttr',
            'olas_id_tto',
        ],
    ],

    'sanam_operatingsystemarchitectures' => [
        'sanam_items_operatingsystems' => 'operatingsystemarchitectures_id',
    ],

    'sanam_operatingsystemeditions' => [
        'sanam_items_operatingsystems' => 'operatingsystemeditions_id',
    ],

    'sanam_operatingsystemkernels' => [
        'sanam_operatingsystemkernelversions' => 'operatingsystemkernels_id',
    ],

    'sanam_operatingsystemkernelversions' => [
        'sanam_items_operatingsystems' => 'operatingsystemkernelversions_id',
    ],

    'sanam_operatingsystems' => [
        'sanam_items_operatingsystems' => 'operatingsystems_id',
        'sanam_softwareversions'        => 'operatingsystems_id',
    ],

    'sanam_operatingsystemservicepacks' => [
        'sanam_items_operatingsystems' => 'operatingsystemservicepacks_id',
    ],

    'sanam_operatingsystemversions' => [
        'sanam_items_operatingsystems' => 'operatingsystemversions_id',
    ],

    'sanam_passivedcequipmentmodels' => [
        'sanam_passivedcequipments' => 'passivedcequipmentmodels_id',
    ],

    'sanam_passivedcequipmenttypes' => [
        'sanam_passivedcequipments' => 'passivedcequipmenttypes_id',
    ],

    'sanam_pendingreasons' => [
        '_sanam_pendingreasons_items' => 'pendingreasons_id',
        'sanam_itilreminders' => 'pendingreasons_id',
        'sanam_itilfollowuptemplates' => 'pendingreasons_id',
        'sanam_tasktemplates' => 'pendingreasons_id',
    ],

    'sanam_pdumodels' => [
        'sanam_pdus' => 'pdumodels_id',
    ],

    'sanam_pdus' => [
        '_sanam_pdus_racks' => 'pdus_id',
    ],

    'sanam_pdutypes' => [
        'sanam_pdus' => 'pdutypes_id',
    ],

    'sanam_peripheralmodels' => [
        'sanam_peripherals' => 'peripheralmodels_id',
    ],

    'sanam_peripheraltypes' => [
        'sanam_peripherals' => 'peripheraltypes_id',
    ],

    'sanam_phonemodels' => [
        'sanam_phones' => 'phonemodels_id',
    ],

    'sanam_phonepowersupplies' => [
        'sanam_phones' => 'phonepowersupplies_id',
    ],

    'sanam_phonetypes' => [
        'sanam_phones' => 'phonetypes_id',
    ],

    'sanam_planningeventcategories' => [
        'sanam_planningexternalevents' => 'planningeventcategories_id',
        'sanam_planningexternaleventtemplates' => 'planningeventcategories_id',
    ],

    'sanam_planningexternaleventtemplates' => [
        'sanam_planningexternalevents' => 'planningexternaleventtemplates_id',
    ],

    'sanam_plugs' => [
        '_sanam_items_plugs' => 'plugs_id',
    ],

    'sanam_printermodels' => [
        '_sanam_cartridgeitems_printermodels' => 'printermodels_id',
        'sanam_printers'                      => 'printermodels_id',
    ],

    'sanam_printers' => [
        '_sanam_cartridges'              => 'printers_id',
        '_sanam_printerlogs'             => ['items_id', 'itemtype'],
        '_sanam_printers_cartridgeinfos' => 'printers_id',
    ],

    'sanam_printertypes' => [
        'sanam_printers' => 'printertypes_id',
    ],

    'sanam_problems' => [
        '_sanam_changes_problems'   => 'problems_id',
        '_sanam_groups_problems'    => 'problems_id',
        '_sanam_items_problems'     => 'problems_id',
        '_sanam_itils_projects'     => [['items_id', 'itemtype']],
        '_sanam_itilfollowups'      => [['items_id', 'itemtype']],
        '_sanam_itilsolutions'      => [['items_id', 'itemtype']],
        '_sanam_problemcosts'       => 'problems_id',
        '_sanam_problems_problems'  => [
            'problems_id_1',
            'problems_id_2',
        ],
        '_sanam_problems_suppliers' => 'problems_id',
        '_sanam_problems_tickets'   => 'problems_id',
        '_sanam_problems_users'     => 'problems_id',
        '_sanam_problemtasks'       => 'problems_id',
    ],

    'sanam_problemtemplates' => [
        'sanam_entities'                         => 'problemtemplates_id',
        'sanam_itilcategories'                   => [
            'problemtemplates_id',
        ],
        'sanam_problems'                         => 'problemtemplates_id',
        '_sanam_problemtemplatehiddenfields'     => 'problemtemplates_id',
        '_sanam_problemtemplatemandatoryfields'  => 'problemtemplates_id',
        '_sanam_problemtemplatepredefinedfields' => 'problemtemplates_id',
        '_sanam_problemtemplatereadonlyfields'   => 'problemtemplates_id',
        'sanam_profiles'                         => 'problemtemplates_id',
    ],

    'sanam_profiles' => [
        '_sanam_knowbaseitems_profiles'         => 'profiles_id',
        '_sanam_profilerights'                  => 'profiles_id',
        '_sanam_profiles_reminders'             => 'profiles_id',
        '_sanam_profiles_rssfeeds'              => 'profiles_id',
        '_sanam_profiles_users'                 => 'profiles_id',
        'sanam_users'                           => 'profiles_id',
    ],

    'sanam_projects' => [
        '_sanam_itils_projects'      => 'projects_id',
        '_sanam_items_projects'      => 'projects_id',
        '_sanam_projectcosts'        => 'projects_id',
        'sanam_projects'             => 'projects_id',
        '_sanam_projecttasks'        => 'projects_id',
        'sanam_projecttasktemplates' => 'projects_id',
        '_sanam_projectteams'        => 'projects_id',
    ],

    'sanam_projectstates' => [
        'sanam_projects'             => 'projectstates_id',
        'sanam_projecttasks'         => 'projectstates_id',
        'sanam_projecttasktemplates' => 'projectstates_id',
    ],

    'sanam_projecttasks' => [
        '_sanam_projecttasklinks'     => [
            'projecttasks_id_source',
            'projecttasks_id_target',
        ],
        'sanam_projecttasks'          => 'projecttasks_id',
        '_sanam_projecttasks_tickets' => 'projecttasks_id',
        '_sanam_projecttaskteams'     => 'projecttasks_id',
        'sanam_projecttasktemplates'  => 'projecttasks_id',
    ],

    'sanam_projecttasktemplates' => [
        'sanam_projecttasks' => 'projecttasktemplates_id',
    ],

    'sanam_projecttasktypes' => [
        'sanam_projecttasks'         => 'projecttasktypes_id',
        'sanam_projecttasktemplates' => 'projecttasktypes_id',
    ],

    'sanam_projecttypes' => [
        'sanam_projects' => 'projecttypes_id',
    ],

    'sanam_rackmodels' => [
        'sanam_racks' => 'rackmodels_id',
    ],

    'sanam_racks' => [
        '_sanam_items_racks' => 'racks_id',
        '_sanam_pdus_racks'  => 'racks_id',
    ],

    'sanam_racktypes' => [
        'sanam_racks' => 'racktypes_id',
    ],

    'sanam_reminders' => [
        '_sanam_entities_reminders'   => 'reminders_id',
        '_sanam_groups_reminders'     => 'reminders_id',
        '_sanam_profiles_reminders'   => 'reminders_id',
        '_sanam_remindertranslations' => 'reminders_id',
        '_sanam_reminders_users'      => 'reminders_id',
    ],

    'sanam_requesttypes' => [
        'sanam_itilfollowups'         => 'requesttypes_id',
        'sanam_itilfollowuptemplates' => 'requesttypes_id',
        'sanam_tickets'               => 'requesttypes_id',
        'sanam_users'                 => 'default_requesttypes_id',
    ],

    'sanam_reservationitems' => [
        '_sanam_reservations' => 'reservationitems_id',
    ],

    'sanam_rssfeeds' => [
        '_sanam_entities_rssfeeds' => 'rssfeeds_id',
        '_sanam_groups_rssfeeds'   => 'rssfeeds_id',
        '_sanam_profiles_rssfeeds' => 'rssfeeds_id',
        '_sanam_rssfeeds_users'    => 'rssfeeds_id',
    ],

    'sanam_rules' => [
        'sanam_refusedequipments' => 'rules_id',
        '_sanam_ruleactions'      => 'rules_id',
        '_sanam_rulecriterias'    => 'rules_id',
        'sanam_rulematchedlogs'   => 'rules_id',
    ],

    'sanam_savedsearches' => [
        '_sanam_savedsearches_alerts' => 'savedsearches_id',
        '_sanam_savedsearches_users'  => 'savedsearches_id',
    ],

    'sanam_slalevels' => [
        '_sanam_slalevelactions'   => 'slalevels_id',
        '_sanam_slalevelcriterias' => 'slalevels_id',
        '_sanam_slalevels_tickets' => 'slalevels_id',
        'sanam_tickets'            => 'slalevels_id_ttr',
    ],

    'sanam_slas' => [
        'sanam_slalevels' => 'slas_id',
        'sanam_tickets'   => [
            'slas_id_ttr',
            'slas_id_tto',
        ],
    ],

    'sanam_slms' => [
        '_sanam_olas' => 'slms_id',
        '_sanam_slas' => 'slms_id',
    ],

    'sanam_snmpcredentials' => [
        'sanam_networkequipments' => 'snmpcredentials_id',
        'sanam_printers'          => 'snmpcredentials_id',
        'sanam_unmanageds'        => 'snmpcredentials_id',
    ],

    'sanam_socketmodels' => [
        'sanam_cables' => [
            'socketmodels_id_endpoint_a',
            'socketmodels_id_endpoint_b',
        ],
        'sanam_sockets' => 'socketmodels_id',
    ],

    'sanam_sockets' => [
        'sanam_cables' => [
            'sockets_id_endpoint_a',
            'sockets_id_endpoint_b',
        ],
    ],

    'sanam_softwarecategories' => [
        '_sanam_softwarecategories' => 'softwarecategories_id',
        'sanam_softwares'           => 'softwarecategories_id',
    ],

    'sanam_softwarelicenses' => [
        '_sanam_items_softwarelicenses'     => 'softwarelicenses_id',
        '_sanam_softwarelicenses'           => 'softwarelicenses_id',
        '_sanam_softwarelicenses_users'             => 'softwarelicenses_id',
    ],

    'sanam_softwarelicensetypes' => [
        'sanam_softwarelicenses'      => 'softwarelicensetypes_id',
        '_sanam_softwarelicensetypes' => 'softwarelicensetypes_id',
    ],

    'sanam_softwares' => [
        '_sanam_softwarelicenses' => 'softwares_id',
        'sanam_softwares'         => 'softwares_id',
        '_sanam_softwareversions' => 'softwares_id',
    ],

    'sanam_softwareversions' => [
        '_sanam_items_softwareversions'     => 'softwareversions_id',
        'sanam_softwarelicenses'            => [
            'softwareversions_id_buy',
            'softwareversions_id_use',
        ],
    ],

    'sanam_solutiontemplates' => [
        'sanam_pendingreasons' => 'solutiontemplates_id',
    ],

    'sanam_solutiontypes' => [
        'sanam_itilsolutions'     => 'solutiontypes_id',
        'sanam_solutiontemplates' => 'solutiontypes_id',
    ],

    'sanam_states' => [
        'sanam_appliances'                => 'states_id',
        'sanam_assets_assets'             => 'states_id',
        'sanam_cables'                    => 'states_id',
        'sanam_certificates'              => 'states_id',
        'sanam_clusters'                  => 'states_id',
        'sanam_computers'                 => 'states_id',
        'sanam_contracts'                 => 'states_id',
        'sanam_databaseinstances'         => 'states_id',
        'sanam_enclosures'                => 'states_id',
        'sanam_items_devicebatteries'     => 'states_id',
        'sanam_items_devicecameras'       => 'states_id',
        'sanam_items_devicecases'         => 'states_id',
        'sanam_items_devicecontrols'      => 'states_id',
        'sanam_items_devicedrives'        => 'states_id',
        'sanam_items_devicefirmwares'     => 'states_id',
        'sanam_items_devicegenerics'      => 'states_id',
        'sanam_items_devicegraphiccards'  => 'states_id',
        'sanam_items_deviceharddrives'    => 'states_id',
        'sanam_items_devicememories'      => 'states_id',
        'sanam_items_devicemotherboards'  => 'states_id',
        'sanam_items_devicenetworkcards'  => 'states_id',
        'sanam_items_devicepcis'          => 'states_id',
        'sanam_items_devicepowersupplies' => 'states_id',
        'sanam_items_deviceprocessors'    => 'states_id',
        'sanam_items_devicesensors'       => 'states_id',
        'sanam_items_devicesimcards'      => 'states_id',
        'sanam_items_devicesoundcards'    => 'states_id',
        'sanam_lines'                     => 'states_id',
        'sanam_monitors'                  => 'states_id',
        'sanam_networkequipments'         => 'states_id',
        'sanam_passivedcequipments'       => 'states_id',
        'sanam_pdus'                      => 'states_id',
        'sanam_peripherals'               => 'states_id',
        'sanam_phones'                    => 'states_id',
        'sanam_printers'                  => 'states_id',
        'sanam_racks'                     => 'states_id',
        'sanam_softwarelicenses'          => 'states_id',
        'sanam_softwareversions'          => 'states_id',
        'sanam_states'                    => 'states_id',
        'sanam_unmanageds'                => 'states_id',
    ],

    'sanam_suppliers' => [
        '_sanam_changes_suppliers'   => 'suppliers_id',
        '_sanam_contacts_suppliers'  => 'suppliers_id',
        '_sanam_contracts_suppliers' => 'suppliers_id',
        'sanam_infocoms'             => 'suppliers_id',
        '_sanam_problems_suppliers'  => 'suppliers_id',
        '_sanam_suppliers_tickets'   => 'suppliers_id',
    ],

    'sanam_suppliertypes' => [
        'sanam_suppliers' => 'suppliertypes_id',
    ],

    'sanam_taskcategories' => [
        'sanam_changetasks'    => 'taskcategories_id',
        'sanam_problemtasks'   => 'taskcategories_id',
        'sanam_taskcategories' => 'taskcategories_id',
        'sanam_tasktemplates'  => 'taskcategories_id',
        'sanam_tickettasks'    => 'taskcategories_id',
    ],

    'sanam_tasktemplates' => [
        'sanam_changetasks'  => 'tasktemplates_id',
        'sanam_problemtasks' => 'tasktemplates_id',
        'sanam_tickettasks'  => 'tasktemplates_id',
    ],

    'sanam_ticketrecurrents' => [
        '_sanam_items_ticketrecurrents' => 'ticketrecurrents_id',
    ],

    'sanam_tickets' => [
        '_sanam_changes_tickets'       => 'tickets_id',
        'sanam_documents'              => 'tickets_id',
        '_sanam_groups_tickets'        => 'tickets_id',
        '_sanam_items_tickets'         => 'tickets_id',
        '_sanam_itils_projects'        => [['items_id', 'itemtype']],
        '_sanam_itilfollowups'         => [['items_id', 'itemtype']],
        '_sanam_itils_validationsteps' => [['items_id', 'itemtype']],
        '_sanam_itilsolutions'         => [['items_id', 'itemtype']],
        '_sanam_olalevels_tickets'     => 'tickets_id',
        '_sanam_problems_tickets'      => 'tickets_id',
        '_sanam_projecttasks_tickets'  => 'tickets_id',
        '_sanam_slalevels_tickets'     => 'tickets_id',
        '_sanam_suppliers_tickets'     => 'tickets_id',
        '_sanam_ticketcosts'           => 'tickets_id',
        '_sanam_tickets_contracts'     => 'tickets_id',
        '_sanam_tickets_tickets'       => [
            'tickets_id_1',
            'tickets_id_2',
        ],
        '_sanam_tickets_users'         => 'tickets_id',
        '_sanam_ticketsatisfactions'   => 'tickets_id',
        '_sanam_tickettasks'           => 'tickets_id',
        '_sanam_ticketvalidations'     => 'tickets_id',
    ],

    'sanam_tickettemplates' => [
        'sanam_entities'                        => 'tickettemplates_id',
        'sanam_itilcategories'                  => [
            'tickettemplates_id_incident',
            'tickettemplates_id_demand',
        ],
        'sanam_profiles'                        => 'tickettemplates_id',
        'sanam_tickets'                         => 'tickettemplates_id',
        'sanam_ticketrecurrents'                => 'tickettemplates_id',
        '_sanam_tickettemplatehiddenfields'     => 'tickettemplates_id',
        '_sanam_tickettemplatemandatoryfields'  => 'tickettemplates_id',
        '_sanam_tickettemplatepredefinedfields' => 'tickettemplates_id',
        '_sanam_tickettemplatereadonlyfields'   => 'tickettemplates_id',
    ],

    'sanam_transfers' => [
        'sanam_entities' => 'transfers_id',
    ],

    'sanam_usercategories' => [
        'sanam_users' => 'usercategories_id',
    ],

    'sanam_users' => [
        'sanam_appliances'             => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_assets_assets'            => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_cables'                   => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_cartridgeitems'           => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_certificates'             => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_changes'                  => [
            'users_id_recipient',
            'users_id_lastupdater',
        ],
        '_sanam_changes_users'           => 'users_id',
        'sanam_changetasks'              => [
            'users_id',
            'users_id_editor',
            'users_id_tech',
        ],
        'sanam_changevalidations'        => [
            'users_id',
            'users_id_validate',
        ],
        'sanam_clusters'                 => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_computers'                => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_consumableitems'          => [
            'users_id_tech',
            'users_id',
        ],
        '_sanam_dashboards_dashboards'   => 'users_id',
        'sanam_dashboards_filters'       => 'users_id',
        'sanam_databaseinstances'        => [
            'users_id_tech',
            'users_id',
        ],
        '_sanam_displaypreferences'      => 'users_id',
        'sanam_domains'                  => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_domainrecords'            => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_documents'                => 'users_id',
        'sanam_documents_items'          => 'users_id',
        'sanam_enclosures'               => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_forms_answerssets'        => 'users_id',
        '_sanam_groups_users'            => 'users_id',
        'sanam_items_devicesimcards'     => [
            'users_id_tech',
            'users_id',
        ],
        '_sanam_items_kanbans'           => 'users_id',
        'sanam_itilcategories'           => 'users_id',
        'sanam_itilfollowups'            => [
            'users_id',
            'users_id_editor',
        ],
        'sanam_itilsolutions'            => [
            'users_id_approval',
            'users_id_editor',
            'users_id',
        ],
        'sanam_knowbaseitems'            => 'users_id',
        'sanam_knowbaseitems_comments'   => 'users_id',
        'sanam_knowbaseitems_revisions'  => 'users_id',
        '_sanam_knowbaseitems_users'     => 'users_id',
        'sanam_knowbaseitemtranslations' => 'users_id',
        'sanam_lines'                    => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_monitors'                 => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_networkequipments'        => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_notepads'                 => [
            'users_id',
            'users_id_lastupdater',
        ],
        'sanam_notimportedemails'        => 'users_id',
        '_sanam_objectlocks'             => 'users_id',
        'sanam_passivedcequipments'      => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_pdus'                     => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_peripherals'              => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_phones'                   => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_planningexternalevents'   => 'users_id',
        'sanam_planningrecalls'          => 'users_id',
        'sanam_printers'                 => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_problems'                 => [
            'users_id_recipient',
            'users_id_lastupdater',
        ],
        '_sanam_problems_users'          => 'users_id',
        'sanam_problemtasks'             => [
            'users_id',
            'users_id_editor',
            'users_id_tech',
        ],
        '_sanam_profiles_users'          => 'users_id',
        'sanam_projects'                 => 'users_id',
        'sanam_projecttasks'             => 'users_id',
        'sanam_projecttasktemplates'     => 'users_id',
        'sanam_racks'                    => [
            'users_id_tech',
            'users_id',
        ],
        '_sanam_reminders'               => 'users_id',
        '_sanam_reminders_users'         => 'users_id',
        '_sanam_remindertranslations'    => 'users_id',
        'sanam_reservations'             => 'users_id',
        'sanam_rssfeeds'                 => 'users_id',
        '_sanam_rssfeeds_users'          => 'users_id',
        '_sanam_savedsearches'           => 'users_id',
        '_sanam_savedsearches_users'     => 'users_id',
        'sanam_softwarelicenses'         => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_softwares'                => [
            'users_id_tech',
            'users_id',
        ],
        'sanam_tasktemplates'            => 'users_id_tech',
        'sanam_tickets'                  => [
            'users_id_recipient',
            'users_id_lastupdater',
        ],
        '_sanam_tickets_users'           => 'users_id',
        'sanam_tickettasks'              => [
            'users_id',
            'users_id_editor',
            'users_id_tech',
        ],
        'sanam_ticketvalidations'        => [
            'users_id',
            'users_id_validate',
        ],
        'sanam_unmanageds'               => [
            'users_id_tech',
            'users_id',
        ],
        '_sanam_useremails'              => 'users_id',
        'sanam_users'                    => 'users_id_supervisor',
        '_sanam_validatorsubstitutes'     => [
            'users_id',
            'users_id_substitute',
        ],
        '_sanam_softwarelicenses_users'          => 'users_id',
        '_sanam_contracts_users'         => 'users_id',
    ],

    'sanam_usertitles' => [
        'sanam_contacts' => 'usertitles_id',
        'sanam_users'    => 'usertitles_id',
    ],

    'sanam_validationsteps' => [
        'sanam_itilvalidationtemplates' => 'validationsteps_id',
        'sanam_itils_validationsteps' => 'validationsteps_id',
    ],

    'sanam_virtualmachinestates' => [
        'sanam_itemvirtualmachines' => 'virtualmachinestates_id',
    ],

    'sanam_virtualmachinesystems' => [
        'sanam_itemvirtualmachines' => 'virtualmachinesystems_id',
    ],

    'sanam_virtualmachinetypes' => [
        'sanam_itemvirtualmachines' => 'virtualmachinetypes_id',
    ],

    'sanam_vlans' => [
        '_sanam_ipnetworks_vlans'   => 'vlans_id',
        '_sanam_networkports_vlans' => 'vlans_id',
    ],

    'sanam_wifinetworks' => [
        'sanam_networkportwifis' => 'wifinetworks_id',
    ],
    'sanam_webhooks' => [
        '_sanam_queuedwebhooks' => 'webhooks_id',
    ],

    'sanam_webhookcategories' => [
        'sanam_webhookcategories'    => 'webhookcategories_id',
        'sanam_webhooks'             => 'webhookcategories_id',
    ],

];

$add_mapping_entry = static function (string $source_table, string $target_table_key, string|array $relation_fields) use (&$RELATION) {
    if (!array_key_exists($source_table, $RELATION)) {
        $RELATION[$source_table] = [];
    }
    if (!array_key_exists($target_table_key, $RELATION[$source_table])) {
        $RELATION[$source_table][$target_table_key] = [];
    }
    if (!is_array($RELATION[$source_table][$target_table_key])) {
        $RELATION[$source_table][$target_table_key] = [$RELATION[$source_table][$target_table_key]];
    }

    if (!in_array($relation_fields, $RELATION[$source_table][$target_table_key], true)) {
        $RELATION[$source_table][$target_table_key][] = $relation_fields;
    }
};

// Add polymorphic relations based on configuration.
global $CFG_SANAM;
$specifically_managed_types = [
    Agent::class, // FIXME Agent should be a CommonDBChild with $mustBeAttached=true
    Consumable::class, // Consumables are handled manually to redefine `date_out` to `null`
    DatabaseInstance::class, // FIXME DatabaseInstance should be a CommonDBChild with $mustBeAttached=true
    Item_Cluster::class, // FIXME $mustBeAttached_1 and $mustBeAttached_2 should probably be set to true
    Item_Enclosure::class, // FIXME $mustBeAttached_1 and $mustBeAttached_2 should probably be set to true
    Item_Rack::class, // FIXME $mustBeAttached_1 and $mustBeAttached_2 should probably be set to true
];
$polymorphic_types_mapping = [
    Agent::class                   => $CFG_SANAM['agent_types'],
    Appliance_Item::class          => $CFG_SANAM['appliance_types'],
    Appliance_Item_Relation::class => $CFG_SANAM['appliance_relation_types'],
    Certificate_Item::class        => $CFG_SANAM['certificate_types'],
    Change_Item::class             => $CFG_SANAM['ticket_types'],
    Consumable::class              => $CFG_SANAM['consumables_types'],
    Contract_Item::class           => $CFG_SANAM['contract_types'],
    DatabaseInstance::class        => $CFG_SANAM['databaseinstance_types'],
    Document_Item::class           => Document::getItemtypesThatCanHave(),
    Domain_Item::class             => $CFG_SANAM['domain_types'],
    Infocom::class                 => Infocom::getItemtypesThatCanHave(),
    Item_Cluster::class            => $CFG_SANAM['cluster_types'],
    Item_Disk::class               => $CFG_SANAM['disk_types'],
    Item_Enclosure::class          => $CFG_SANAM['rackable_types'],
    Item_Kanban::class             => $CFG_SANAM['kanban_types'],
    Item_OperatingSystem::class    => $CFG_SANAM['operatingsystem_types'],
    Item_Problem::class            => $CFG_SANAM['ticket_types'],
    Item_Project::class            => $CFG_SANAM['project_asset_types'],
    Item_Rack::class               => $CFG_SANAM['rackable_types'],
    Item_SoftwareLicense::class    => $CFG_SANAM['software_types'],
    Item_SoftwareVersion::class    => $CFG_SANAM['software_types'],
    Item_Ticket::class             => $CFG_SANAM['ticket_types'],
    ItemAntivirus::class           => $CFG_SANAM['itemantivirus_types'],
    ItemVirtualMachine::class      => $CFG_SANAM['itemvirtualmachines_types'],
    KnowbaseItem_Item::class       => $CFG_SANAM['kb_types'],
    NetworkPort::class             => $CFG_SANAM['networkport_types'],
    ReservationItem::class         => $CFG_SANAM['reservation_types'],
    Socket::class                  => $CFG_SANAM['socket_types'],
    Item_Plug::class               => $CFG_SANAM['plug_types'],
];
foreach (Item_Devices::getDeviceTypes() as $itemdevice_itemtype) {
    $source_itemtypes = $itemdevice_itemtype::itemAffinity();
    if (in_array('*', $source_itemtypes)) {
        $source_itemtypes = $CFG_SANAM['itemdevices_types'];
    }
    $polymorphic_types_mapping[$itemdevice_itemtype] = $source_itemtypes;
    $specifically_managed_types[] = $itemdevice_itemtype; // Item_Devices is handled manually to take care of `keep_devices` option
}
$polymorphic_types_mapping[VObject::class] = [];
foreach ($CFG_SANAM['planning_types'] as $planning_itemtype) {
    if (is_a($planning_itemtype, CalDAVCompatibleItemInterface::class, true)) {
        $polymorphic_types_mapping[VObject::class][] = $planning_itemtype;
    }
}

foreach ($polymorphic_types_mapping as $target_itemtype => $source_itemtypes) {
    foreach ($source_itemtypes as $source_itemtype) {
        $target_table_key_prefix = '';
        if (
            in_array($target_itemtype, $specifically_managed_types)
            || (
                is_a($target_itemtype, CommonDBChild::class, true)
                && $target_itemtype::$itemtype === 'itemtype'
                && $target_itemtype::$items_id === 'items_id'
                && $target_itemtype::$mustBeAttached === true
            )
            || (
                is_a($target_itemtype, CommonDBRelation::class, true)
                && (
                    (
                        $target_itemtype::$itemtype_1 === 'itemtype'
                        && $target_itemtype::$items_id_1 === 'items_id'
                        && $target_itemtype::$mustBeAttached_1 === true
                    )
                    || (
                        $target_itemtype::$itemtype_2 === 'itemtype'
                        && $target_itemtype::$items_id_2 === 'items_id'
                        && $target_itemtype::$mustBeAttached_2 === true
                    )
                )
            )
        ) {
            // If item must be attached, target table key has to be prefixed by "_"
            // to be ignored by `CommonDBTM::cleanRelationData()`. Indeed, without usage of this prefix,
            // related item will be preserved with its foreign key defined to 0, making it an unwanted orphaned item.
            $target_table_key_prefix = '_';
        }
        /** @var class-string<CommonDBTM> $target_itemtype */
        $target_table_key = $target_table_key_prefix . $target_itemtype::getTable();
        $source_table     = $source_itemtype::getTable();

        $add_mapping_entry($source_table, $target_table_key, ['items_id', 'itemtype']);
    }
}

// IPAddress specific case
// mainitems_id/mainitemtype are mainly a copy of item related to source NetworkPort
foreach ($CFG_SANAM['networkport_types'] as $source_itemtype) {
    $target_table_key = IPAddress::getTable();
    $source_table     = $source_itemtype::getTable();

    $add_mapping_entry($source_table, $target_table_key, ['mainitems_id', 'mainitemtype']);
}

// Asset_PeripheralAsset specific case
foreach ($CFG_SANAM['directconnect_types'] as $directconnect_itemtype) {
    $target_table_key = '_' . Asset_PeripheralAsset::getTable();
    $source_table     = $directconnect_itemtype::getTable();

    $add_mapping_entry($source_table, $target_table_key, ['itemtype_peripheral', 'items_id_peripheral']);
}
foreach (Asset_PeripheralAsset::getPeripheralHostItemtypes() as $peripheralhost_itemtype) {
    $target_table_key = '_' . Asset_PeripheralAsset::getTable();
    $source_table     = $peripheralhost_itemtype::getTable();

    $add_mapping_entry($source_table, $target_table_key, ['itemtype_asset', 'items_id_asset']);
}

// Multiple groups assignments
$assignable_itemtypes = $CFG_SANAM['assignable_types'];
foreach ($assignable_itemtypes as $assignable_itemtype) {
    $source_table_key = $assignable_itemtype::getTable();

    $add_mapping_entry($source_table_key, '_sanam_groups_items', ['itemtype', 'items_id']);
}

// `Item_Device*` relations automatically handled by `Device::cleanDBOnPurge()`
foreach ($CFG_SANAM['device_types'] as $device_itemtype) {
    if (!is_a($device_itemtype, CommonDevice::class, true)) {
        continue; // unexpected declaration
    }

    $item_device_itemtype = $device_itemtype::getItem_DeviceType();
    if (is_a($item_device_itemtype, CommonDBTM::class, true)) {
        $add_mapping_entry(
            $device_itemtype::getTable(),
            '_' . $item_device_itemtype::getTable(),
            $device_itemtype::getForeignKeyField()
        );
    }
}
