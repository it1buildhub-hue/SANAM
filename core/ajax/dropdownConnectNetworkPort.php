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

/**
 * @since 0.84
 */

use Sanam\DBAL\QueryExpression;

global $DB;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("networking", UPDATE);

// Make a select box
if (
    class_exists($_POST["itemtype"])
    && isset($_POST["item"])
    && ($item = getItemForItemtype($_POST["itemtype"]))
) {
    $table = $item::getTable();

    $joins = [];
    $name_field = new QueryExpression("'' AS " . $DB::quoteName('socketname'));

    if ($_POST['instantiation_type'] == 'NetworkPortEthernet') {
        $name_field = 'sanam_sockets.name AS socketname';
        $joins = [
            'sanam_networkportethernets'   => [
                'ON'  => [
                    'sanam_networkportethernets'   => 'id',
                    'sanam_networkports'           => 'id',
                ],
            ],
            'sanam_sockets'              => [
                'ON'  => [
                    'sanam_networkports'   => 'id',
                    'sanam_sockets'        => 'networkports_id',
                ],
            ],
        ];
    }

    $criteria = [
        'SELECT'    => [
            'sanam_networkports_networkports.id AS wid',
            'sanam_networkports.id AS did',
            "$table.name AS cname",
            'sanam_networkports.name AS nname',
            $name_field,
        ],
        'DISTINCT'  => true,
        'FROM'      => $table,
        'LEFT JOIN' => [
            'sanam_networkports'  => [
                'ON'  => [
                    'sanam_networkports'  => 'items_id',
                    $table               => 'id', [
                        'AND' => [
                            'sanam_networkports.items_id'           => $_POST['item'],
                            'sanam_networkports.itemtype'           => $item::class,
                            'sanam_networkports.instantiation_type' => $_POST['instantiation_type'],
                        ],
                    ],
                ],
            ],
            'sanam_networkports_networkports' => [
                'ON'  => [
                    'sanam_networkports_networkports' => 'networkports_id_1',
                    'sanam_networkports'              => 'id', [
                        'OR'  => [
                            'sanam_networkports_networkports.networkports_id_2' => new QueryExpression($DB::quoteName('sanam_networkports.id')),
                        ],
                    ],
                ],
            ],
        ] + $joins,
        'WHERE'     => [
            'sanam_networkports_networkports.id' => null,
            'NOT'                               => ['sanam_networkports.id' => null],
            'sanam_networkports.id'              => ['<>', $_POST['networkports_id']],
        ],
        'ORDERBY'   => 'sanam_networkports.id',
    ];

    if ($item->maybeDeleted()) {
        $criteria['WHERE']["$table.is_deleted"] = 0;
    }

    if ($item->maybeTemplate()) {
        $criteria['WHERE']["$table.is_template"] = 0;
    }

    $iterator = $DB->request($criteria);

    echo "<br>";

    $values = [];
    foreach ($iterator as $data) {
        // Device name + port name
        $output = $output_long = $data['cname'];

        if (!empty($data['nname'])) {
            $output      = sprintf(__('%1$s - %2$s'), $output, $data['nname']);
            //TRANS: %1$s is device name, %2$s is port name
            $output_long = sprintf(__('%1$s - The port %2$s'), $output_long, $data['nname']);
        }

        // display netpoint (which will be copied)
        if (!empty($data['socketname'])) {
            $output      = sprintf(__('%1$s - %2$s'), $output, $data['socketname']);
            //TRANS: %1$s is a string (device name - port name...), %2$s is network outlet name
            $output_long = sprintf(__('%1$s - Network outlet %2$s'), $output_long, $data['socketname']);
        }
        $ID = $data['did'];

        if ($_SESSION["sanamis_ids_visible"] || empty($output) || empty($output_long)) {
            $output      = sprintf(__('%1$s (%2$s)'), $output, $ID);
            $output_long = sprintf(__('%1$s (%2$s)'), $output_long, $ID);
        }
        $values[$ID] = $output_long;
    }
    Dropdown::showFromArray($_POST['myname'], $values, ['display_emptychoice' => true]);
}
