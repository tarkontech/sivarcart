<div class="siv-stack">
    <header class="siv-page-header">
        <h1 class="siv-page-header__title">{{ __('sivarcart-core::admin.settings.title') }}</h1>
        <p class="siv-page-header__description">{{ __('sivarcart-core::admin.settings.description') }}</p>
    </header>

    @if ($notice !== null)
        <div class="siv-alert siv-alert--success">{{ $notice }}</div>
    @endif

    <x-sivarcart-core::card>
        <form wire:submit="save" class="siv-form">
            <label class="siv-field">
                <span class="siv-field__label">{{ __('sivarcart-core::admin.settings.name') }}</span>
                <input type="text" wire:model="name" class="siv-input">
                @error('name')
                    <span class="siv-field__error">{{ $message }}</span>
                @enderror
            </label>

            <label class="siv-field">
                <span class="siv-field__label">{{ __('sivarcart-core::admin.settings.legal_name') }}</span>
                <input type="text" wire:model="legal_name" class="siv-input">
                @error('legal_name')
                    <span class="siv-field__error">{{ $message }}</span>
                @enderror
            </label>

            <label class="siv-field">
                <span class="siv-field__label">{{ __('sivarcart-core::admin.settings.tax_id') }}</span>
                <input type="text" wire:model="tax_id" class="siv-input">
                @error('tax_id')
                    <span class="siv-field__error">{{ $message }}</span>
                @enderror
            </label>

            <label class="siv-field">
                <span class="siv-field__label">{{ __('sivarcart-core::admin.settings.email') }}</span>
                <input type="email" wire:model="email" class="siv-input">
                @error('email')
                    <span class="siv-field__error">{{ $message }}</span>
                @enderror
            </label>

            <label class="siv-field">
                <span class="siv-field__label">{{ __('sivarcart-core::admin.settings.phone') }}</span>
                <input type="text" wire:model="phone" class="siv-input">
                @error('phone')
                    <span class="siv-field__error">{{ $message }}</span>
                @enderror
            </label>

            <div class="siv-actions">
                <button type="submit" class="siv-button siv-button--primary" wire:loading.attr="disabled">
                    {{ __('sivarcart-core::admin.settings.save') }}
                </button>
            </div>
        </form>
    </x-sivarcart-core::card>
</div>
