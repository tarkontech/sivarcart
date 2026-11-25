<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Support;

use Illuminate\Support\Facades\Config;

/**
 * The settings of the media of the store.
 *
 * The media library of Spatie and the service of the package do not speak the
 * same language: one wants the name of a driver, the other wants the size of
 * a thumbnail in kilobytes. This class reads the configuration of the package
 * and answers what both of them need.
 */
final class Media
{
    /**
     * The disk where the files of the store live.
     */
    public static function disk(): string
    {
        $disk = Config::get('sivarcart-core.media.disk', 'public');

        if (! is_string($disk) || $disk === '') {
            return 'public';
        }

        return $disk;
    }

    /**
     * How big a file may be, in kilobytes.
     */
    public static function maxSize(): int
    {
        $size = Config::get('sivarcart-core.media.max_size', 10240);

        if (! is_int($size) || $size < 1) {
            return 10240;
        }

        return $size;
    }

    /**
     * The kinds of file the store accepts.
     *
     * @return list<string>
     */
    public static function allowedMimes(): array
    {
        $mimes = Config::get('sivarcart-core.media.mimes', []);

        if (! is_array($mimes)) {
            return [];
        }

        $allowed = [];

        foreach ($mimes as $mime) {
            if (is_string($mime) && $mime !== '') {
                $allowed[] = strtolower($mime);
            }
        }

        return $allowed;
    }

    /**
     * The sizes of the thumbnails the store wants.
     *
     * @return array<string, array{width: int, height: int}>
     */
    public static function thumbnailSizes(): array
    {
        $thumbnails = Config::get('sivarcart-core.media.thumbnails', []);

        if (! is_array($thumbnails)) {
            return [];
        }

        $sizes = [];

        foreach ($thumbnails as $name => $size) {
            if (! is_string($name) || ! is_array($size)) {
                continue;
            }

            $width = $size['width'] ?? null;
            $height = $size['height'] ?? null;

            if (! is_int($width) || ! is_int($height)) {
                continue;
            }

            $sizes[$name] = ['width' => $width, 'height' => $height];
        }

        return $sizes;
    }

    /**
     * The name of the smallest thumbnail of the store.
     *
     * It is the one the catalog shows when it lists the products.
     */
    public static function firstThumbnailName(): ?string
    {
        $names = array_keys(self::thumbnailSizes());

        return $names[0] ?? null;
    }

    /**
     * Whether the thumbnails are built in the background.
     */
    public static function queuesConversions(): bool
    {
        return (bool) Config::get('sivarcart-core.media.queue_conversions', false);
    }
}
