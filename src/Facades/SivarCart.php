<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tarkontech\SivarCart\SivarCart
 */
class SivarCart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tarkontech\SivarCart\SivarCart::class;
    }
}
