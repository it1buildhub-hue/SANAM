<?php

namespace SanamPlugin\Sanamassets;

use Session;

/** Adds the SANAM entries to the Assets menu, only for users who may open them. */
final class Menu
{
    public static function redefine(array $menu): array
    {
        if (!isset($menu['assets']['content'])) {
            return $menu;
        }

        $entries = [];
        $canManage = Session::haveRight(Schema::RIGHT_MANAGE, READ);
        $canAudit  = Session::haveRight(Schema::RIGHT_AUDIT, READ);

        if ($canAudit || $canManage) {
            $entries['sanam_scan'] = [
                'title' => __('Scan asset', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/scan.php',
                'icon'  => 'ti ti-scan',
            ];
        }
        if ($canManage) {
            $entries['sanam_dashboard'] = [
                'title' => __('SANAM dashboard', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/dashboard.php',
                'icon'  => 'ti ti-layout-dashboard',
            ];
            $entries['sanam_campaigns'] = [
                'title' => __('Audit campaigns', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/campaigns.php',
                'icon'  => 'ti ti-clipboard-check',
            ];
            $entries['sanam_custody'] = [
                'title' => __('Custody', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/custody.php',
                'icon'  => 'ti ti-user-check',
            ];
            $entries['sanam_labels'] = [
                'title' => __('Labels', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/labels.php',
                'icon'  => 'ti ti-tag',
            ];
            $entries['sanam_telecom'] = [
                'title' => __('Telecom lines', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/telecom.php',
                'icon'  => 'ti ti-phone',
            ];
            $entries['sanam_reports'] = [
                'title' => __('Reports and exports', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/reports.php',
                'icon'  => 'ti ti-file-spreadsheet',
            ];
            $entries['sanam_companies'] = [
                'title' => __('Companies', 'sanamassets'),
                'page'  => '/plugins/sanamassets/front/companies.php',
                'icon'  => 'ti ti-building',
            ];
        }

        foreach ($entries as $key => $entry) {
            $menu['assets']['content'][$key] = $entry;
        }
        return $menu;
    }
}
