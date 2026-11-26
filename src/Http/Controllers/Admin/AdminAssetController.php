<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers the assets of the administration panel.
 *
 * The bundle travels with the package, so it is always the one that goes with
 * the version of the panel the merchant installed. The merchant that wants to
 * change it publishes the source of the panel and compiles its own bundle,
 * and then the package stops serving its own.
 */
final class AdminAssetController
{
    /**
     * Answer the stylesheet of the panel.
     */
    public function css(Request $request): Response
    {
        return $this->serve('admin.css', 'text/css; charset=utf-8');
    }

    /**
     * Answer the script of the panel.
     */
    public function js(Request $request): Response
    {
        return $this->serve('admin.js', 'text/javascript; charset=utf-8');
    }

    /**
     * Answer a piece of the bundle of the panel.
     *
     * The bundle is split in pieces, one per page, and the browser asks for
     * them by their name. The name never has a folder in it, so the file that
     * is answered is always one of the files of the bundle and never one of
     * the files of the application.
     */
    public function chunk(Request $request, string $chunk): Response
    {
        if (preg_match('/^[A-Za-z0-9_.-]+\.(js|css)$/', $chunk) !== 1) {
            abort(404, 'El archivo del panel no existe.');
        }

        return $this->serve($chunk, str_ends_with($chunk, '.css') ? 'text/css; charset=utf-8' : 'text/javascript; charset=utf-8');
    }

    /**
     * Answer a file of the bundle of the panel.
     */
    private function serve(string $file, string $type): Response
    {
        $path = dirname(__DIR__, 4).'/resources/dist/'.$file;

        if (! is_file($path)) {
            abort(404, 'El archivo del panel no existe.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            abort(500, 'El archivo del panel no se pudo leer.');
        }

        return response($contents, 200, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
