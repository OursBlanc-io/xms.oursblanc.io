@php
    $tabs = $data['tabs'] ?? [];
@endphp

<section @if(!empty($data['anchor_id'])) id="{{ $data['anchor_id'] }}" @endif class="ob-section ob-section--dark">
    <div class="ob-grid-bg"></div>
    <div class="ob-wrap" style="position:relative;z-index:2">
        <div class="ob-section-head ob-reveal">
            <p class="ob-eyebrow ob-eyebrow--dk">{{ $data['eyebrow'] ?? '' }}</p>
            <h2>{{ $data['title'] ?? '' }}</h2>
            <p>{{ $data['subtitle'] ?? '' }}</p>
        </div>

        <div class="ob-player ob-reveal" data-tabbed-showcase>
            <div class="ob-fmt-list" role="tablist">
                @foreach($tabs as $i => $tab)
                    <button
                        type="button"
                        class="ob-fmt-btn @if($i === 0) ob-on @endif"
                        data-tsc-tab="{{ $i }}"
                        role="tab"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    >
                        <h3>{{ $tab['title'] ?? '' }}</h3>
                        @if(!empty($tab['description']))
                            <p>{{ $tab['description'] }}</p>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="ob-tsc-panels" data-tsc-panels>
                @foreach($tabs as $i => $tab)
                    <div class="ob-tsc-panel @if($i === 0) is-active @endif" data-tsc-panel="{{ $i }}">
                        @forelse ($tab['resolved_content'] ?? [] as $nestedBlock)
                            @if($nestedBlock['view'])
                                @include($nestedBlock['view'], ['data' => $nestedBlock['data'], 'page' => $page])
                            @endif
                        @empty
                            <p class="ob-tsc-empty">{{ __('No content yet.') }}</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
