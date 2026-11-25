<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Livewire\Admin\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use SivarcartCore\SivarcartCore\Services\StoreService;

/**
 * The settings of the store.
 *
 * The staff of the store changes the name of the store, its tax id and the
 * ways it reaches its buyers. The settings are written by the service of the
 * store, the same one the rest of the package reads.
 */
class StoreSettings extends Component
{
    /**
     * The name of the store.
     */
    public string $name = '';

    /**
     * The legal name of the store.
     */
    public string $legal_name = '';

    /**
     * The tax id of the store.
     */
    public string $tax_id = '';

    /**
     * The email of the store.
     */
    public string $email = '';

    /**
     * The phone of the store.
     */
    public string $phone = '';

    /**
     * The message that tells the staff what happened.
     */
    public ?string $notice = null;

    /**
     * Take the settings of the store.
     */
    public function mount(): void
    {
        $store = app(StoreService::class)->current();

        if ($store === null) {
            return;
        }

        $this->name = (string) $store->name;
        $this->legal_name = (string) $store->legal_name;
        $this->tax_id = (string) $store->tax_id;
        $this->email = (string) $store->email;
        $this->phone = (string) $store->phone;
    }

    /**
     * Save the settings of the store.
     */
    public function save(): void
    {
        $attributes = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        app(StoreService::class)->update($attributes);

        $this->notice = __('sivarcart-core::admin.settings.saved');
    }

    /**
     * Render the settings.
     */
    public function render(): View
    {
        return view('sivarcart-core::admin.pages.settings.index');
    }
}
