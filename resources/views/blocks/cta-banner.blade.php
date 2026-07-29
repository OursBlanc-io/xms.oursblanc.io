@php
    $style = $data['style'] ?? 'dark';
@endphp
<section
    @if(!empty($data['anchor_id'])) id="{{ $data['anchor_id'] }}" @endif
    class="ob-section ob-final @if($style === 'dark') ob-breathe @endif"
    data-style="{{ $style }}"
>
    <div class="ob-wrap ob-final-inner">
        @if(!empty($data['title']))
            <h2>{{ $data['title'] }}</h2>
        @endif
        @if(!empty($data['subtitle']))
            <p>{{ $data['subtitle'] }}</p>
        @endif
        @if(!empty($data['cta_label']) && !empty($data['cta_url']))
            <a href="{{ $data['cta_url'] }}" class="ob-btn ob-btn--primary">
                {{ $data['cta_label'] }} <span class="ob-btn__arrow">→</span>
            </a>
        @endif
    </div>
</section>
