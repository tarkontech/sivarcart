@php
    /**
     * The bundle of the panel.
     *
     * The package serves its own bundle, so a merchant that installs the
     * package has the panel without Node and without a build step. A merchant
     * that builds its own bundle gets it: the package looks at the manifest of
     * the application and, when the entry of the merchant is in it, loads that
     * one instead.
     */
    $bundle = \SivarcartCore\SivarcartCore\Admin\AdminBundle::resolve();
@endphp
@if ($bundle->type === \SivarcartCore\SivarcartCore\Admin\AdminBundle::TYPE_VITE)
    @vite([$bundle->entry])
@else
    <link rel="stylesheet" href="{{ route('sivarcart-core.admin.assets.css') }}?v={{ $bundle->version }}">
    <script type="module" src="{{ route('sivarcart-core.admin.assets.js') }}?v={{ $bundle->version }}"></script>
@endif
