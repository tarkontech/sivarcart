<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Rmirandasv\Wompi\Enums\FormaPago;

/**
 * Casts a Wompi payment method to its enum.
 *
 * Unlike a plain enum cast this one tolerates values the package does not
 * know about yet: Wompi adds payment methods over time and an unknown method
 * must never make a stored transaction unreadable.
 *
 * @implements CastsAttributes<FormaPago|null, string|FormaPago>
 */
class WompiPaymentMethod implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?FormaPago
    {
        if ($value === null || $value === '') {
            return null;
        }

        return FormaPago::fromRaw(is_string($value) ? $value : null);
    }

    /**
     * Transform the attribute to its underlying model values.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof FormaPago) {
            return $value->value;
        }

        return (string) $value;
    }
}
