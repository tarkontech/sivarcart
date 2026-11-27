@php
    /**
     * The bundle of the store.
     *
     * The package serves its own bundle, so a merchant that installs the
     * package has the store without Node and without a build step. A merchant
     * that builds its own bundle gets it: the package looks at the manifest
     * of the application and, when the entry of the merchant is in it, loads
     * that one instead.
     */
    $bundle = \SivarcartCore\SivarcartCore\Storefront\StorefrontBundle::resolve();
@endphp
@if ($bundle->type === \SivarcartCore\SivarcartCore\Storefront\StorefrontBundle::TYPE_VITE)
    @vite([$bundle->entry])
@else
    {{--
        The version of the bundle is a folder of the address and not a query of
        it: the pieces of the bundle ask for each other with a relative
        address, so the folder travels with them and the browser never mixes
        the bundle of a previous version with the one that is installed.
    --}}
    <link rel="stylesheet" href="{{ route('sivarcart-core.storefront.assets.css', ['version' => $bundle->version]) }}">
    <script type="module" src="{{ route('sivarcart-core.storefront.assets.js', ['version' => $bundle->version]) }}"></script>
@endif
