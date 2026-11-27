<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Tarkontech\SivarCart\Enums\PaymentMethodType;
use Tarkontech\SivarCart\Models\PaymentMethod;
use Tarkontech\SivarCart\Support\Slug;

/**
 * The payment methods of the store, as the staff of the store sees them.
 *
 * The service of the storefront only reads the methods that are active. This
 * one reads all of them and writes them, because a method that the store is
 * still preparing is not active yet.
 *
 * The credentials of a method never leave the server: the panel is told
 * whether they are there and not what they are.
 */
final class PaymentMethodService
{
    /**
     * The payment methods of the store.
     *
     * @return LengthAwarePaginator<int, PaymentMethod>
     */
    public function paginate(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return PaymentMethod::query()
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
     * One payment method of the store, by its uuid.
     */
    public function find(string $uuid): ?PaymentMethod
    {
        return PaymentMethod::query()->where('uuid', $uuid)->first();
    }

    /**
     * Every payment method of the store.
     *
     * @return list<PaymentMethod>
     */
    public function all(): array
    {
        return array_values(
            PaymentMethod::query()
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->all(),
        );
    }

    /**
     * Create a payment method of the store.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): PaymentMethod
    {
        $method = new PaymentMethod;

        $method->fill($this->attributes($attributes, null));
        $method->save();

        return $method->refresh();
    }

    /**
     * Write a payment method of the store.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(PaymentMethod $method, array $attributes): PaymentMethod
    {
        $method->fill($this->attributes($attributes, $method));
        $method->save();

        return $method->refresh();
    }

    /**
     * Take a payment method off the store.
     *
     * The method is not erased: the payments that were collected with it keep
     * pointing at it, and the staff can still read it.
     */
    public function delete(PaymentMethod $method): void
    {
        $method->delete();
    }

    /**
     * The attributes of a payment method, ready to be filled.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function attributes(array $attributes, ?PaymentMethod $method): array
    {
        $name = trim((string) ($attributes['name'] ?? ''));

        $code = trim((string) ($attributes['code'] ?? ''));

        if ($code === '') {
            $code = Slug::uniqueCode($name, 'sivarcart_payment_methods', $method?->getKey());
        }

        $type = PaymentMethodType::tryFrom((string) ($attributes['type'] ?? '')) ?? PaymentMethodType::OFFLINE;

        $filled = [
            'code' => $code,
            'name' => $name,
            'type' => $type->value,
            'instructions' => $this->text($attributes['instructions'] ?? null),
            'icon' => $this->text($attributes['icon'] ?? null),
            'position' => (int) ($attributes['position'] ?? 0),
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ];

        $driver = $this->text($attributes['driver'] ?? null);

        $filled['driver'] = $type === PaymentMethodType::ONLINE ? $driver : null;

        $credentials = $this->credentials($attributes['credentials'] ?? null);

        $filled['credentials'] = $credentials === [] ? null : $credentials;

        return $filled;
    }

    /**
     * The credentials of a method, as the form carries them.
     *
     * The form carries a list of pairs, so the staff can write the
     * credentials of any driver and not only the ones the package knows.
     *
     * @return array<string, string>
     */
    private function credentials(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $credentials = [];

        foreach ($value as $pair) {
            if (! is_array($pair)) {
                continue;
            }

            $key = trim((string) ($pair['key'] ?? ''));
            $secret = $pair['value'] ?? null;

            if ($key === '' || ! is_string($secret) || $secret === '') {
                continue;
            }

            $credentials[$key] = $secret;
        }

        return $credentials;
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
}
