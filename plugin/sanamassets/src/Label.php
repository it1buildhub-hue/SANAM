<?php

namespace SanamPlugin\Sanamassets;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Config;
use RuntimeException;
use Session;

/**
 * QR labels: stable scan URL, label lifecycle events, and printable markup.
 *
 * A label is an identity sticker, so printing or reprinting never creates an asset.
 */
final class Label
{
    /** Lifecycle derived from the most recent event for an asset. */
    private const STATUS_BY_EVENT = [
        'generated' => 'not_printed',
        'printed'   => 'printed',
        'reprinted' => 'printed',
        'attached'  => 'attached',
        'scanned'   => 'scan_verified',
        'damaged'   => 'damaged',
    ];

    public static function baseUrl(): string
    {
        global $CFG_SANAM;

        $values = Config::getConfigurationValues('plugin:sanamassets');
        $base = trim((string) ($values['base_url'] ?? ''));
        if ($base === '') {
            $base = (string) $CFG_SANAM['url_base'];
        }
        return rtrim($base, '/');
    }

    /**
     * Stable scan URL. Refuses loopback hosts: a label printed with localhost
     * can never be scanned from another device.
     */
    public static function scanUrl(string $asset_id): string
    {
        if (!AssetId::isValid($asset_id)) {
            throw new RuntimeException('Invalid asset identity: ' . $asset_id);
        }

        $base = self::baseUrl();
        $host = strtolower((string) parse_url($base, PHP_URL_HOST));
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)) {
            throw new RuntimeException(
                'Set the server address in SANAM plugin settings. localhost and 127.0.0.1 cannot be used in labels.'
            );
        }

        return $base . '/plugins/sanamassets/front/scan.php?asset=' . rawurlencode($asset_id);
    }

    public static function qrSvg(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(160, 2), new SvgImageBackEnd());
        return (new Writer($renderer))->writeString($url);
    }

    public static function record(string $asset_id, string $event, ?string $comment = null): void
    {
        global $DB;

        $DB->insert('sanam_plugin_sanamassets_labels', [
            'asset_id'      => $asset_id,
            'event'         => $event,
            'users_id'      => (int) Session::getLoginUserID(false),
            'comment'       => $comment,
            'date_creation' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Current label lifecycle state for an asset, or "not_recorded" when there is no history. */
    public static function status(string $asset_id): string
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['event'],
            'FROM'   => 'sanam_plugin_sanamassets_labels',
            'WHERE'  => ['asset_id' => $asset_id],
            'ORDER'  => ['id DESC'],
            'LIMIT'  => 1,
        ]);
        foreach ($iterator as $row) {
            return self::STATUS_BY_EVENT[$row['event']] ?? 'not_recorded';
        }
        return 'not_recorded';
    }

    /**
     * Labels that were generated but never printed or attached.
     *
     * @return list<string> asset identities
     */
    public static function missing(): array
    {
        global $DB;

        $missing = [];
        foreach ($DB->request(['SELECT' => ['asset_id'], 'FROM' => 'sanam_plugin_sanamassets_assetids', 'WHERE' => ['status' => 'active']]) as $row) {
            $state = self::status($row['asset_id']);
            if (in_array($state, ['not_recorded', 'not_printed'], true)) {
                $missing[] = $row['asset_id'];
            }
        }
        return $missing;
    }
}
