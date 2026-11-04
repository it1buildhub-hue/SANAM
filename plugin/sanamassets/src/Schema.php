<?php

namespace SanamPlugin\Sanamassets;

use ProfileRight;

/**
 * Database schema and rights for the SANAM Assets plugin.
 *
 * All tables use InnoDB + utf8mb4 so SANAM's own transaction handling applies.
 */
final class Schema
{
    public const RIGHT_MANAGE = 'plugin_sanamassets';
    public const RIGHT_AUDIT  = 'plugin_sanamassets_audit';

    /** @return array<string, string> table name => CREATE TABLE statement */
    public static function tables(): array
    {
        $engine = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        return [
            'sanam_plugin_sanamassets_sequences' => "CREATE TABLE sanam_plugin_sanamassets_sequences (
                id INT UNSIGNED NOT NULL,
                next_value BIGINT UNSIGNED NOT NULL,
                PRIMARY KEY (id)
            ) $engine",

            'sanam_plugin_sanamassets_assetids' => "CREATE TABLE sanam_plugin_sanamassets_assetids (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                asset_id VARCHAR(20) NOT NULL,
                itemtype VARCHAR(255) NOT NULL,
                items_id INT UNSIGNED NOT NULL,
                status ENUM('active','retired') NOT NULL DEFAULT 'active',
                legacy_ref VARCHAR(255) NULL,
                date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                date_retired TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uk_asset_id (asset_id),
                KEY idx_item (itemtype, items_id)
            ) $engine",

            'sanam_plugin_sanamassets_conflicts' => "CREATE TABLE sanam_plugin_sanamassets_conflicts (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                itemtype VARCHAR(255) NOT NULL,
                items_id INT UNSIGNED NOT NULL,
                other_itemtype VARCHAR(255) NOT NULL,
                other_items_id INT UNSIGNED NOT NULL,
                field VARCHAR(32) NOT NULL,
                value VARCHAR(255) NOT NULL,
                status ENUM('open','accepted','rejected') NOT NULL DEFAULT 'open',
                users_id INT UNSIGNED NOT NULL DEFAULT 0,
                users_id_reviewer INT UNSIGNED NULL,
                date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                date_reviewed TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_status (status)
            ) $engine",

            'sanam_plugin_sanamassets_labels' => "CREATE TABLE sanam_plugin_sanamassets_labels (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                asset_id VARCHAR(20) NOT NULL,
                event ENUM('generated','printed','attached','scanned','reprinted','damaged') NOT NULL,
                users_id INT UNSIGNED NOT NULL DEFAULT 0,
                comment VARCHAR(255) NULL,
                date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_asset (asset_id)
            ) $engine",

            'sanam_plugin_sanamassets_campaigns' => "CREATE TABLE sanam_plugin_sanamassets_campaigns (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                entities_id INT UNSIGNED NOT NULL DEFAULT 0,
                kind ENUM('baseline','periodic','spot') NOT NULL DEFAULT 'periodic',
                status ENUM('draft','active','review','closed') NOT NULL DEFAULT 'draft',
                date_start TIMESTAMP NULL DEFAULT NULL,
                date_end TIMESTAMP NULL DEFAULT NULL,
                users_id_owner INT UNSIGNED NOT NULL DEFAULT 0,
                closed_at TIMESTAMP NULL DEFAULT NULL,
                closed_by INT UNSIGNED NULL,
                reopen_count INT UNSIGNED NOT NULL DEFAULT 0,
                date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                date_mod TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_status (status)
            ) $engine",

            'sanam_plugin_sanamassets_campaignlogs' => "CREATE TABLE sanam_plugin_sanamassets_campaignlogs (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaigns_id INT UNSIGNED NOT NULL,
                action VARCHAR(40) NOT NULL,
                reason VARCHAR(255) NULL,
                users_id INT UNSIGNED NOT NULL DEFAULT 0,
                date_action TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_campaign (campaigns_id)
            ) $engine",

            'sanam_plugin_sanamassets_campaignareas' => "CREATE TABLE sanam_plugin_sanamassets_campaignareas (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaigns_id INT UNSIGNED NOT NULL,
                locations_id INT UNSIGNED NOT NULL DEFAULT 0,
                name VARCHAR(255) NOT NULL,
                status ENUM('not_started','in_progress','done','inaccessible') NOT NULL DEFAULT 'not_started',
                inaccessible_reason VARCHAR(255) NULL,
                users_id_auditor INT UNSIGNED NULL,
                date_done TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_campaign (campaigns_id)
            ) $engine",

            'sanam_plugin_sanamassets_expected' => "CREATE TABLE sanam_plugin_sanamassets_expected (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaigns_id INT UNSIGNED NOT NULL,
                itemtype VARCHAR(255) NOT NULL,
                items_id INT UNSIGNED NOT NULL,
                asset_id VARCHAR(20) NULL,
                locations_id INT UNSIGNED NOT NULL DEFAULT 0,
                users_id INT UNSIGNED NOT NULL DEFAULT 0,
                states_id INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                UNIQUE KEY uk_expected (campaigns_id, itemtype, items_id)
            ) $engine",

            'sanam_plugin_sanamassets_verifications' => "CREATE TABLE sanam_plugin_sanamassets_verifications (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaigns_id INT UNSIGNED NOT NULL,
                itemtype VARCHAR(255) NOT NULL,
                items_id INT UNSIGNED NOT NULL,
                asset_id VARCHAR(20) NULL,
                areas_id INT UNSIGNED NULL,
                users_id INT UNSIGNED NOT NULL,
                method ENUM('qr','manual','search') NOT NULL,
                locations_id INT UNSIGNED NOT NULL DEFAULT 0,
                users_id_found INT UNSIGNED NULL,
                state_observed VARCHAR(50) NULL,
                comment VARCHAR(255) NULL,
                date_verified TIMESTAMP NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uk_verification (campaigns_id, itemtype, items_id)
            ) $engine",

            'sanam_plugin_sanamassets_scans' => "CREATE TABLE sanam_plugin_sanamassets_scans (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaigns_id INT UNSIGNED NULL,
                asset_id VARCHAR(20) NULL,
                query VARCHAR(255) NULL,
                users_id INT UNSIGNED NOT NULL,
                method ENUM('qr','manual','search') NOT NULL,
                result ENUM('found','not_found','duplicate') NOT NULL,
                date_scan TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_asset (asset_id)
            ) $engine",

            'sanam_plugin_sanamassets_discrepancies' => "CREATE TABLE sanam_plugin_sanamassets_discrepancies (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaigns_id INT UNSIGNED NOT NULL,
                type ENUM('unexpected','location','custody','not_found','damage','label','conflict') NOT NULL,
                itemtype VARCHAR(255) NULL,
                items_id INT UNSIGNED NULL,
                asset_id VARCHAR(20) NULL,
                details TEXT NULL,
                status ENUM('open','approved','rejected') NOT NULL DEFAULT 'open',
                users_id_reviewer INT UNSIGNED NULL,
                date_reviewed TIMESTAMP NULL DEFAULT NULL,
                date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_campaign (campaigns_id, status)
            ) $engine",

            'sanam_plugin_sanamassets_assignments' => "CREATE TABLE sanam_plugin_sanamassets_assignments (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                itemtype VARCHAR(255) NOT NULL,
                items_id INT UNSIGNED NOT NULL,
                asset_id VARCHAR(20) NULL,
                kind ENUM('permanent','temporary','shared') NOT NULL,
                users_id INT UNSIGNED NULL,
                groups_id INT UNSIGNED NULL,
                date_start TIMESTAMP NOT NULL,
                date_expected_return DATE NULL DEFAULT NULL,
                date_returned TIMESTAMP NULL DEFAULT NULL,
                condition_issue VARCHAR(50) NULL,
                condition_return VARCHAR(50) NULL,
                accessories TEXT NULL,
                ack_method ENUM('signed_paper','screen','account','none') NOT NULL DEFAULT 'none',
                note TEXT NULL,
                users_id_issuer INT UNSIGNED NOT NULL DEFAULT 0,
                status ENUM('active','returned') NOT NULL DEFAULT 'active',
                PRIMARY KEY (id),
                KEY idx_item_status (itemtype, items_id, status),
                KEY idx_user_status (users_id, status)
            ) $engine",

            'sanam_plugin_sanamassets_clearances' => "CREATE TABLE sanam_plugin_sanamassets_clearances (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                users_id INT UNSIGNED NOT NULL,
                status ENUM('open','cleared') NOT NULL DEFAULT 'open',
                users_id_approver INT UNSIGNED NULL,
                note TEXT NULL,
                date_creation TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                date_cleared TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_user (users_id, status)
            ) $engine",

            'sanam_plugin_sanamassets_brands' => "CREATE TABLE sanam_plugin_sanamassets_brands (
                entities_id INT UNSIGNED NOT NULL,
                display_name VARCHAR(255) NOT NULL,
                logo_file VARCHAR(255) NULL,
                date_mod TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (entities_id)
            ) $engine",
        ];
    }

    /** Full rights for Super-Admin and manage/audit rights for Admin. */
    public static function grantRights(): void
    {
        global $DB;

        ProfileRight::addProfileRights([self::RIGHT_MANAGE, self::RIGHT_AUDIT]);

        $full = READ | UPDATE | CREATE | DELETE | PURGE;

        // Super-Admin (profile 4): everything. Admin (profile 3): everything except purge.
        foreach ([4 => $full, 3 => $full & ~PURGE] as $profile_id => $bits) {
            foreach ([self::RIGHT_MANAGE, self::RIGHT_AUDIT] as $name) {
                $DB->update(
                    'sanam_profilerights',
                    ['rights' => $bits],
                    ['profiles_id' => $profile_id, 'name' => $name],
                );
            }
        }
    }

    public static function revokeRights(): void
    {
        global $DB;

        $DB->delete('sanam_profilerights', ['name' => [self::RIGHT_MANAGE, self::RIGHT_AUDIT]]);
    }
}
