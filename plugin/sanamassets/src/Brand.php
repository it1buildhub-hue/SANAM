<?php

namespace SanamPlugin\Sanamassets;

use Entity;
use RuntimeException;
use Session;

/**
 * Per-company branding: a display name and a logo for each entity of the company tree.
 *
 * Logo files live in SANAM_VAR_DIR/_plugins/sanamassets/brands/<entities_id>.<ext>. The table keeps
 * only the file name, and no file path is ever sent to the browser.
 */
final class Brand
{
    public const TABLE = 'sanam_plugin_sanamassets_brands';

    private const MAX_BYTES  = 1048576; // 1 MB
    private const MIN_PIXELS = 32;
    private const MAX_PIXELS = 2000;

    /** getimagesize() image type => file extension. Only these three are accepted. */
    private const EXTENSIONS = [
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_WEBP => 'webp',
    ];

    private const MIME_TYPES = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    /** Branding row of a company, or null when it has none. */
    public static function get(int $entities_id): ?array
    {
        global $DB;

        foreach ($DB->request(['FROM' => self::TABLE, 'WHERE' => ['entities_id' => $entities_id], 'LIMIT' => 1]) as $row) {
            return $row;
        }
        return null;
    }

    /** Active entity of the session: the key the core writes in Session::changeActiveEntities(). */
    public static function activeEntity(): int
    {
        return (int) ($_SESSION['sanamactive_entity'] ?? 0);
    }

    /** Folder where the logo files are stored. */
    public static function storageDir(): string
    {
        return SANAM_VAR_DIR . '/_plugins/sanamassets/brands';
    }

    /**
     * URL of the logo page of a company. The version only changes the URL, so that browsers
     * fetch a new logo after it was replaced.
     */
    public static function logoUrl(int $entities_id, ?int $version = null): string
    {
        global $CFG_SANAM;

        $url = ($CFG_SANAM['root_doc'] ?? '') . '/plugins/sanamassets/front/logo.php?entity=' . $entities_id;
        return $version === null ? $url : $url . '&v=' . $version;
    }

    /**
     * Checks an uploaded picture by its content, not by its name: PNG, JPEG or WebP, at most 1 MB,
     * 32 to 2000 pixels on each side. Returns the extension to store the file with.
     *
     * @throws RuntimeException
     */
    public static function checkImage(string $tmp_path, ?string $original_name = null): string
    {
        $name = ($original_name === null || $original_name === '') ? __('The file', 'sanamassets') : $original_name;

        if (!is_file($tmp_path) || !is_readable($tmp_path)) {
            throw new RuntimeException(__('The logo could not be read. Try again.', 'sanamassets'));
        }
        $size = filesize($tmp_path);
        if ($size === false || $size > self::MAX_BYTES) {
            throw new RuntimeException(__('The logo must be 1 MB or smaller.', 'sanamassets'));
        }
        $info = @getimagesize($tmp_path);
        if ($info === false || !isset(self::EXTENSIONS[$info[2]])) {
            throw new RuntimeException(sprintf(__('%s is not a PNG, JPEG or WebP image.', 'sanamassets'), $name));
        }
        [$width, $height] = $info;
        if ($width < self::MIN_PIXELS || $height < self::MIN_PIXELS || $width > self::MAX_PIXELS || $height > self::MAX_PIXELS) {
            throw new RuntimeException(sprintf(
                __('The logo must be between %1$d and %2$d pixels wide and high.', 'sanamassets'),
                self::MIN_PIXELS,
                self::MAX_PIXELS
            ));
        }
        return self::EXTENSIONS[$info[2]];
    }

    /**
     * Saves the display name and, when $uploaded_tmp_path is given, the logo of a company.
     * A refused logo changes nothing: the previous name and logo stay.
     *
     * @throws RuntimeException
     */
    public static function save(int $entities_id, string $display_name, ?string $uploaded_tmp_path, ?string $original_name): void
    {
        global $DB;

        $name = trim($display_name);
        if ($name === '' || mb_strlen($name) > 255) {
            throw new RuntimeException(__('Enter a display name of up to 255 characters.', 'sanamassets'));
        }
        if (!self::entityExists($entities_id)) {
            throw new RuntimeException(__('This company does not exist.', 'sanamassets'));
        }

        $fields = [
            'display_name' => $name,
            'date_mod'     => date('Y-m-d H:i:s'),
        ];

        if ($uploaded_tmp_path !== null) {
            $extension = self::checkImage($uploaded_tmp_path, $original_name);
            $dir = self::storageDir();
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException(__('The logo folder could not be created.', 'sanamassets'));
            }
            $target = $dir . '/' . $entities_id . '.' . $extension;
            $stored = is_uploaded_file($uploaded_tmp_path)
                ? move_uploaded_file($uploaded_tmp_path, $target)
                : copy($uploaded_tmp_path, $target);
            if (!$stored) {
                throw new RuntimeException(__('The logo could not be saved.', 'sanamassets'));
            }
            // The previous logo of this company may have another extension: remove it.
            foreach (glob($dir . '/' . $entities_id . '.*') ?: [] as $old) {
                if ($old !== $target) {
                    unlink($old);
                }
            }
            $fields['logo_file'] = basename($target);
        }

        if (self::get($entities_id) === null) {
            $DB->insert(self::TABLE, ['entities_id' => $entities_id] + $fields);
        } else {
            $DB->update(self::TABLE, $fields, ['entities_id' => $entities_id]);
        }
    }

    /**
     * Creates a company under $parent_id, with its branding row (display name = company name).
     *
     * @throws RuntimeException
     */
    public static function addCompany(string $name, int $parent_id): int
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 255) {
            throw new RuntimeException(__('Enter a company name of up to 255 characters.', 'sanamassets'));
        }
        if (!self::entityExists($parent_id)) {
            throw new RuntimeException(__('The parent company does not exist.', 'sanamassets'));
        }

        $entity = new Entity();
        $id = $entity->add(['name' => $name, 'entities_id' => $parent_id]);
        if ($id === false) {
            throw new RuntimeException(__('The company could not be created.', 'sanamassets'));
        }

        self::save((int) $id, $name, null, null);
        return (int) $id;
    }

    /** Path and MIME type of the stored logo of a company, or null when it has none. */
    public static function logo(int $entities_id): ?array
    {
        $row = self::get($entities_id);
        $file = $row['logo_file'] ?? null;
        if (!is_string($file) || preg_match('/^' . $entities_id . '\.(png|jpg|webp)$/', $file) !== 1) {
            return null;
        }

        $path = self::storageDir() . '/' . $file;
        if (!is_file($path)) {
            return null;
        }
        return [
            'path'  => $path,
            'mime'  => self::MIME_TYPES[pathinfo($file, PATHINFO_EXTENSION)],
            'mtime' => (int) filemtime($path),
        ];
    }

    /** Branding row of the active company, or null when nobody is logged in or it has none. */
    public static function active(): ?array
    {
        if (!Session::getLoginUserID()) {
            return null;
        }
        return self::get(self::activeEntity());
    }

    /**
     * CSS that points the logo variables at the logo of the active company. Empty when that
     * company has no logo.
     */
    public static function css(): string
    {
        $brand = self::active();
        $logo = $brand === null ? null : self::logo((int) $brand['entities_id']);
        if ($logo === null) {
            return '';
        }

        $url = self::logoUrl((int) $brand['entities_id'], $logo['mtime']);
        $value = 'url("' . str_replace(['\\', '"'], ['\\\\', '\\"'], $url) . '") !important';

        $lines = [];
        foreach (['--sanam-logo-light', '--sanam-logo-dark', '--sanam-logo-light-login', '--sanam-logo-dark-login', '--sanam-logo-reduced'] as $variable) {
            $lines[] = "    $variable: $value;";
        }
        return "/* SANAM Assets: logos of the active company. */\n:root {\n" . implode("\n", $lines) . "\n}\n";
    }

    /** Data for the page script: the display name of the active company. Empty when it has none. */
    public static function pageInfo(): array
    {
        $brand = self::active();
        return $brand === null ? [] : ['name' => (string) $brand['display_name']];
    }

    /**
     * Head tags of the logged-in pages (ADD_HEADER_TAG). The tag is fixed; the CSS it loads depends
     * on the active company, so it can be served on every page.
     *
     * @return array<int, array{tag: string, properties: array<string, string>}>
     */
    public static function headerTags(): array
    {
        global $CFG_SANAM;

        return [
            [
                'tag'        => 'link',
                'properties' => [
                    'rel'  => 'stylesheet',
                    'type' => 'text/css',
                    'href' => ($CFG_SANAM['root_doc'] ?? '') . '/plugins/sanamassets/front/brand.php?format=css',
                ],
            ],
        ];
    }

    private static function entityExists(int $entities_id): bool
    {
        global $DB;

        return $DB->request(['FROM' => Entity::getTable(), 'WHERE' => ['id' => $entities_id]])->count() > 0;
    }
}
