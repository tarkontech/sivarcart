@if ($paginator->hasPages())
    <nav class="siv-pagination" role="navigation" aria-label="{{ __('sivarcart-core::admin.pagination.label') }}">
        <button
            type="button"
            class="siv-button siv-button--small"
            wire:click="previousPage('{{ $paginator->getPageName() }}')"
            wire:loading.attr="disabled"
            @disabled($paginator->onFirstPage())
        >
            {{ __('sivarcart-core::admin.pagination.previous') }}
        </button>

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="siv-pagination__gap">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <button
                        type="button"
                        class="siv-pagination__page{{ $page == $paginator->currentPage() ? ' is-active' : '' }}"
                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                    >
                        {{ $page }}
                    </button>
                @endforeach
            @endif
        @endforeach

        <button
            type="button"
            class="siv-button siv-button--small"
            wire:click="nextPage('{{ $paginator->getPageName() }}')"
            wire:loading.attr="disabled"
            @disabled(! $paginator->hasMorePages())
        >
            {{ __('sivarcart-core::admin.pagination.next') }}
        </button>
    </nav>
@endif
