<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \SivarcartCore\SivarcartCore\SivarcartCore
 */
class SivarcartCore extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \SivarcartCore\SivarcartCore\SivarcartCore::class;
    }
}
