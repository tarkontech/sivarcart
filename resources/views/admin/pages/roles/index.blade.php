<div class="siv-stack">
    <header class="siv-page-header">
        <h1 class="siv-page-header__title">{{ __('sivarcart-core::admin.roles.title') }}</h1>
        <p class="siv-page-header__description">{{ __('sivarcart-core::admin.roles.description') }}</p>
    </header>

    <x-sivarcart-core::card>
        <table class="siv-table">
            <thead>
                <tr>
                    <th>{{ __('sivarcart-core::admin.roles.name') }}</th>
                    <th>{{ __('sivarcart-core::admin.roles.slug') }}</th>
                    <th>{{ __('sivarcart-core::admin.roles.permissions') }}</th>
                    <th>{{ __('sivarcart-core::admin.roles.staff') }}</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($roles as $role)
                    <tr wire:key="role-{{ $role->uuid }}">
                        <td>{{ $role->name }}</td>
                        <td><code>{{ $role->slug }}</code></td>
                        <td>
                            @foreach ($role->permissions ?? [] as $permission)
                                <x-sivarcart-core::badge :label="$permission" />
                            @endforeach
                        </td>
                        <td>{{ $role->users_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-sivarcart-core::card>
</div>
