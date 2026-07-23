@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $pages */
    $pages = $data['pages'];
    $facets = $data['facet_filters'] ?? [];
@endphp
<div class="xms-block xms-block--page-list">
    @if (count($facets))
        <form method="GET" class="xms-page-list__filters">
            @foreach ($facets as $facet)
                <label class="xms-page-list__filter">
                    <span>{{ $facet['label'] }}</span>
                    <select name="{{ $facet['key'] }}" onchange="this.form.submit()">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($facet['options'] as $option)
                            <option value="{{ $option }}" @selected($facet['selected'] === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach
            <noscript><button type="submit">{{ __('Apply filters') }}</button></noscript>
        </form>
    @endif

    <ul class="xms-page-list">
        @forelse ($pages as $listedPage)
            <li class="xms-page-list__item">
                <a class="xms-page-list__link" href="{{ \OursBlanc\Xms\Support\PageUrlGenerator::for($listedPage) }}">
                    @if ($listedPage->illustrationUrl())
                        <img class="xms-page-list__illustration" src="{{ $listedPage->illustrationUrl() }}" alt="" loading="lazy">
                    @endif
                    <span class="xms-page-list__title">{{ $listedPage->effectiveListTitle() }}</span>
                </a>
                @if ($listedPage->list_excerpt)
                    <p class="xms-page-list__excerpt">{{ $listedPage->list_excerpt }}</p>
                @endif
                @if ($listedPage->published_at)
                    <time class="xms-page-list__date" datetime="{{ $listedPage->published_at->toAtomString() }}">
                        {{ $listedPage->published_at->format('Y-m-d') }}
                    </time>
                @endif
            </li>
        @empty
            <li class="xms-page-list__empty">{{ __('No pages found.') }}</li>
        @endforelse
    </ul>

    @if ($pages->hasPages())
        <nav class="xms-page-list__pagination" aria-label="Pagination">
            @if ($pages->onFirstPage())
                <span class="xms-page-list__pagination-prev is-disabled">{{ __('Previous') }}</span>
            @else
                <a class="xms-page-list__pagination-prev" href="{{ $pages->previousPageUrl() }}">{{ __('Previous') }}</a>
            @endif

            <span class="xms-page-list__pagination-status">
                {{ __('Page :current of :last', ['current' => $pages->currentPage(), 'last' => $pages->lastPage()]) }}
            </span>

            @if ($pages->hasMorePages())
                <a class="xms-page-list__pagination-next" href="{{ $pages->nextPageUrl() }}">{{ __('Next') }}</a>
            @else
                <span class="xms-page-list__pagination-next is-disabled">{{ __('Next') }}</span>
            @endif
        </nav>
    @endif
</div>
