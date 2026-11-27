<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin;

use Illuminate\Support\Facades\Config;

/**
 * The bundle of the administration panel.
 *
 * The panel travels with the package compiled: the package serves its own
 * JavaScript and its own stylesheet, so a merchant installs the package and
 * the panel is there, without Node and without a build step.
 *
 * A merchant that wants its own interface publishes the source of the panel,
 * writes its own entry and compiles it. Its bundle wins: the package looks at
 * the manifest of the application and, when the entry of the merchant is in
 * it, loads that one instead of its own.
 */
final readonly class AdminBundle
{
    /**
     * The bundle of the package: the package serves it.
     */
    public const string TYPE_PACKAGE = 'package';

    /**
     * The bundle of the application: the application serves it.
     */
    public const string TYPE_VITE = 'vite';

    /**
     * @param  string  $type  Whether the bundle is the one of the package or the one of the application.
     * @param  string|null  $entry  The entry of the application, when the bundle is its own.
     * @param  string  $version  The version of the assets of the panel.
     */
    public function __construct(
        public string $type,
        public ?string $entry = null,
        public string $version = '1',
    ) {
        //
    }

    /**
     * The bundle the panel has to load.
     */
    public static function resolve(): self
    {
        $entry = (string) Config::get('sivarcart-core.admin.bundle.entry', 'resources/js/sivarcart/admin.tsx');
        $directory = (string) Config::get('sivarcart-core.admin.bundle.directory', 'build');

        if (self::manifestHas($directory, $entry)) {
            return new self(type: self::TYPE_VITE, entry: $entry, version: self::version());
        }

        return new self(type: self::TYPE_PACKAGE, version: self::version());
    }

    /**
     * Whether the bundle is the one of the package.
     */
    public function belongsToPackage(): bool
    {
        return $this->type === self::TYPE_PACKAGE;
    }

    /**
     * The version of the assets of the panel.
     *
     * The assets travel with the package, so they are the ones that go with the
     * version of the panel the merchant installed. The number tells the browser
     * that they changed.
     *
     * The number is the hash of the content of the assets and not the date of
     * the files: the address of an asset only changes when its content
     * changes, so the browser never keeps a bundle that is no longer the one
     * the package brought. A date can stay the same —the files of a package
     * arrive with the date of the build— and the cache of the filesystem of a
     * long-lived process can keep a date that already passed.
     */
    public static function version(): string
    {
        return self::versionOf(self::files());
    }

    /**
     * The version of a set of files.
     *
     * The version is the hash of the content of the files, so the address of
     * an asset only changes when the asset changes. A date is not enough: the
     * files of a package arrive with the date of the build, and the cache of
     * the filesystem of a long-lived process can keep a date that already
     * passed.
     *
     * @param  list<string>  $files
     */
    public static function versionOf(array $files): string
    {
        $hash = hash_init('xxh128');

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $contents = file_get_contents($file);

            if ($contents === false) {
                continue;
            }

            hash_update($hash, $contents);
        }

        return hash_final($hash);
    }

    /**
     * The files the panel serves.
     *
     * @return list<string>
     */
    public static function files(): array
    {
        $directory = dirname(__DIR__, 2).'/resources/dist/admin';

        return [
            $directory.'/admin.js',
            $directory.'/admin.css',
        ];
    }

    /**
     * Whether the manifest of the application has the entry of the merchant.
     *
     * The manifest is read defensively: an application that does not build
     * anything with Vite does not have one, and one that has it broken must
     * not take the panel down.
     */
    private static function manifestHas(string $directory, string $entry): bool
    {
        $path = public_path($directory.'/manifest.json');

        if (! is_file($path) || ! is_readable($path)) {
            return false;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return false;
        }

        $manifest = json_decode($contents, true);

        if (! is_array($manifest)) {
            return false;
        }

        return array_key_exists($entry, $manifest);
    }
}
