<section @if(!empty($data['anchor_id'])) id="{{ $data['anchor_id'] }}" @endif class="ob-section ob-final ob-breathe">
    <div class="ob-wrap ob-final-inner">
        <h2>{{ $data['title'] ?? '' }}</h2>
        <p>{{ $data['subtitle'] ?? '' }}</p>
        @if(!empty($data['cta_label']) && !empty($data['cta_url']))
            <a href="{{ $data['cta_url'] }}" class="ob-btn ob-btn--primary">
                {{ $data['cta_label'] }} <span class="ob-btn__arrow">→</span>
            </a>
        @endif
    </div>
</section>
