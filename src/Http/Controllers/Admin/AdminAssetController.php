<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Answers the stylesheet of the administration panel.
 *
 * The stylesheet travels with the package, so it is always the one that goes
 * with the version of the panel the merchant installed. The merchant that
 * wants to change it publishes the views and the stylesheet of the package and
 * writes its own.
 */
final class AdminAssetController
{
    /**
     * Answer the stylesheet of the panel.
     */
    public function __invoke(Request $request): Response
    {
        $path = dirname(__DIR__, 4).'/resources/dist/admin.css';

        if (! is_file($path)) {
            abort(404, 'La hoja de estilos del panel no existe.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            abort(500, 'La hoja de estilos del panel no se pudo leer.');
        }

        return response($contents, 200, [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
