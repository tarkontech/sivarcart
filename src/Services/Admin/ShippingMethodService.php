<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use SivarcartCore\SivarcartCore\Enums\ShippingMethodType;
use SivarcartCore\SivarcartCore\Models\ShippingMethod;
use SivarcartCore\SivarcartCore\Support\Slug;

/**
 * The shipping methods of the store, as the staff of the store sees them.
 *
 * The service of the storefront only reads the methods that are active. This
 * one reads all of them and writes them, because a method that the store is
 * still preparing is not active yet.
 */
final class ShippingMethodService
{
    /**
     * The shipping methods of the store.
     *
     * @return LengthAwarePaginator<int, ShippingMethod>
     */
    public function paginate(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return ShippingMethod::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);
    }

    /**
     * One shipping method of the store, by its uuid.
     */
    public function find(string $uuid): ?ShippingMethod
    {
        return ShippingMethod::query()->where('uuid', $uuid)->first();
    }

    /**
     * Every shipping method of the store.
     *
     * @return list<ShippingMethod>
     */
    public function all(): array
    {
        return array_values(
            ShippingMethod::query()
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->all(),
        );
    }

    /**
     * Create a shipping method of the store.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ShippingMethod
    {
        $method = new ShippingMethod;

        $method->fill($this->attributes($attributes, null));
        $method->save();

        return $method->refresh();
    }

    /**
     * Write a shipping method of the store.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(ShippingMethod $method, array $attributes): ShippingMethod
    {
        $method->fill($this->attributes($attributes, $method));
        $method->save();

        return $method->refresh();
    }

    /**
     * Remove a shipping method of the store.
     *
     * The orders that were shipped with it keep the name and the code of the
     * method, so the history of the store is not rewritten.
     */
    public function delete(ShippingMethod $method): void
    {
        $method->delete();
    }

    /**
     * The attributes of a shipping method, ready to be filled.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(array $attributes, ?ShippingMethod $method): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));

        $code = trim((string) ($attributes['code'] ?? ''));

        if ($code === '') {
            $code = Slug::uniqueCode($name, 'sivarcart_shipping_methods', $method?->getKey());
        }

        $type = ShippingMethodType::tryFrom((string) ($attributes['type'] ?? '')) ?? ShippingMethodType::FLAT_RATE;

        return [
            'code' => $code,
            'name' => $name,
            'type' => $type->value,
            'price' => $this->decimal($attributes['price'] ?? 0) ?? 0,
            'min_order_amount' => $this->decimal($attributes['min_order_amount'] ?? null),
            'max_order_amount' => $this->decimal($attributes['max_order_amount'] ?? null),
            'estimated_days' => $this->integer($attributes['estimated_days'] ?? null),
            'instructions' => $this->text($attributes['instructions'] ?? null),
            'position' => (int) ($attributes['position'] ?? 0),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ];
    }

    /**
     * A text that is not an empty string.
     */
    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * A number with two decimals.
     */
    private function decimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    /**
     * A whole number.
     */
    private function integer(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
