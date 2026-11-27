<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Http\Controllers\Storefront;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers the assets of the store the shopper buys in.
 *
 * The bundle travels with the package, so it is always the one that goes with
 * the version of the store the merchant installed. The merchant that wants to
 * change it publishes the source of the store and compiles its own bundle,
 * and then the package stops serving its own.
 *
 * The assets live in a folder with the version of the bundle. The pieces of
 * the bundle ask for each other with a relative address, so the version
 * travels with them: a browser that kept the bundle of a previous version
 * never mixes it with the bundle of the one that is installed, and a mixed
 * bundle is a store that does not work.
 */
final class StorefrontAssetController
{
    /**
     * Answer the stylesheet of the store.
     */
    public function css(Request $request, string $version): Response
    {
        return $this->serve('storefront.css', 'text/css; charset=utf-8');
    }

    /**
     * Answer the script of the store.
     */
    public function js(Request $request, string $version): Response
    {
        return $this->serve('storefront.js', 'text/javascript; charset=utf-8');
    }

    /**
     * Answer a piece of the bundle of the store.
     *
     * The bundle is split in pieces, one per page, and the browser asks for
     * them by their name. The name never has a folder in it, so the file that
     * is answered is always one of the files of the bundle and never one of
     * the files of the application.
     */
    public function chunk(Request $request, string $version, string $chunk): Response
    {
        if (preg_match('/^[A-Za-z0-9_.-]+\.(js|css)$/', $chunk) !== 1) {
            abort(404, 'El archivo de la tienda no existe.');
        }

        return $this->serve($chunk, str_ends_with($chunk, '.css') ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8');
    }

    /**
     * Answer a file of the bundle of the store.
     */
    private function serve(string $file, string $type): Response
    {
        $path = dirname(__DIR__, 4).'/resources/dist/storefront/'.$file;

        if (! is_file($path)) {
            abort(404, 'El archivo de la tienda no existe.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            abort(500, 'El archivo de la tienda no se pudo leer.');
        }

        return response($contents, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
