<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('sivarcart-core::admin.setup.title') }}</title>

    <link rel="stylesheet" href="{{ route('sivarcart-core.admin.assets.css') }}?v={{ $adminAssetVersion }}">
</head>
<body class="siv-body siv-body--centered">
    <main class="siv-auth">
        <h1 class="siv-auth__title">{{ __('sivarcart-core::admin.setup.title') }}</h1>

        <p class="siv-auth__text">{{ __('sivarcart-core::admin.setup.body') }}</p>

        <pre class="siv-code"><code>{{ $model }}</code></pre>

        <p class="siv-auth__text">{{ __('sivarcart-core::admin.setup.trait') }}</p>

        <pre class="siv-code"><code>use SivarcartCore\SivarcartCore\Models\Concerns\HasStoreRoles;

class {{ class_basename($model) }} extends Authenticatable
{
    use HasStoreRoles;
}</code></pre>

        <p class="siv-auth__text">{{ __('sivarcart-core::admin.setup.roles') }}</p>

        <pre class="siv-code"><code>php artisan db:seed --class="SivarcartCore\SivarcartCore\Database\Seeders\StoreRoleSeeder"</code></pre>
    </main>
</body>
</html>
