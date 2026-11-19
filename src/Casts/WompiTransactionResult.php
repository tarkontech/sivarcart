<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Rmirandasv\Wompi\Enums\ResultadoTransaccion;

/**
 * Casts a Wompi transaction result to its enum.
 *
 * The webhook sends the result as a string ("ExitosaAprobada") while the API
 * sends an integer enumeration. Only the integer form is storable, and the
 * untouched payload always remains available in the "raw_payload" column.
 *
 * @implements CastsAttributes<ResultadoTransaccion|null, int|string|ResultadoTransaccion>
 */
class WompiTransactionResult implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?ResultadoTransaccion
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ResultadoTransaccion::fromRaw(is_numeric($value) ? (int) $value : null);
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof ResultadoTransaccion) {
            return $value->value;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return ResultadoTransaccion::tryFrom((int) $value)?->value;
    }
}
