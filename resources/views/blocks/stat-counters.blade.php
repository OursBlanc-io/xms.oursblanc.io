<section @if(!empty($data['anchor_id'])) id="{{ $data['anchor_id'] }}" @endif class="ob-section ob-section--dark-2">
    <div class="ob-wrap ob-att-grid">
        <div class="ob-att-stats ob-reveal">
            @foreach($data['stats'] ?? [] as $stat)
                <div class="ob-stat">
                    <b class="ob-count" data-to="{{ $stat['value'] ?? 0 }}" data-prefix="{{ $stat['prefix'] ?? '' }}" data-suffix="{{ $stat['suffix'] ?? '' }}">{{ $stat['prefix'] ?? '' }}0{{ $stat['suffix'] ?? '' }}</b>
                    <span>{{ $stat['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>

        <div class="ob-att-copy ob-reveal">
            <p class="ob-eyebrow ob-eyebrow--dk">{{ $data['eyebrow'] ?? '' }}</p>
            @foreach($data['paragraphs'] ?? [] as $paragraph)
                <p>{!! $paragraph['text'] ?? '' !!}</p>
            @endforeach
        </div>
    </div>
</section>
