<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Admin\Pages;

use Illuminate\Http\Request;
use Tarkontech\SivarCart\Admin\AdminPage;
use Tarkontech\SivarCart\Admin\AdminPageProps;
use Tarkontech\SivarCart\Admin\AdminPresenter;
use Tarkontech\SivarCart\Models\Store;
use Tarkontech\SivarCart\Services\StoreService;
use Tarkontech\SivarCart\Support\Auth;

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
            return [
                'settings' => self::empty(),
                'logo' => null,
                'favicon' => null,
                'canUpdate' => Auth::hasStorePermission(Auth::user(), 'settings.update'),
            ];
        }

        return [
            'settings' => [
                'name' => (string) ($store->name ?? ''),
                'legal_name' => (string) ($store->legal_name ?? ''),
                'tax_id' => (string) ($store->tax_id ?? ''),
                'email' => (string) ($store->email ?? ''),
                'phone' => (string) ($store->phone ?? ''),
            ],
            'logo' => AdminPresenter::fileUrl($store->logo_path),
            'favicon' => AdminPresenter::fileUrl($store->favicon_path),
            'canUpdate' => Auth::hasStorePermission(Auth::user(), 'settings.update'),
        ];
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
