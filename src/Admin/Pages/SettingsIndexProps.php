<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Admin\Pages;

use Illuminate\Http\Request;
use SivarcartCore\SivarcartCore\Admin\AdminPage;
use SivarcartCore\SivarcartCore\Admin\AdminPageProps;
use SivarcartCore\SivarcartCore\Models\Store;
use SivarcartCore\SivarcartCore\Services\StoreService;

/**
 * The data of the settings of the store.
 *
 * The settings are the ones the store shows to its buyers, and they are
 * written by the service of the store, the same one the rest of the package
 * reads.
 */
final readonly class SettingsIndexProps implements AdminPageProps
{
    /**
     * @return array<string, mixed>
     */
    public function props(Request $request, AdminPage $page): array
    {
        $store = app(StoreService::class)->current();

        if (! $store instanceof Store) {
            return ['settings' => self::empty()];
        }

        return ['settings' => [
            'name' => (string) ($store->name ?? ''),
            'legal_name' => (string) ($store->legal_name ?? ''),
            'tax_id' => (string) ($store->tax_id ?? ''),
            'email' => (string) ($store->email ?? ''),
            'phone' => (string) ($store->phone ?? ''),
        ]];
    }

    /**
     * The settings of a store that does not exist yet.
     *
     * @return array{name: string, legal_name: string, tax_id: string, email: string, phone: string}
     */
    private static function empty(): array
    {
        return [
            'name' => '',
            'legal_name' => '',
            'tax_id' => '',
            'email' => '',
            'phone' => '',
        ];
    }
}
