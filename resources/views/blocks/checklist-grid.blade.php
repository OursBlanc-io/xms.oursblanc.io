<section class="ob-section ob-section--frost">
    <div class="ob-wrap">
        <div class="ob-section-head ob-reveal">
            <p class="ob-eyebrow">{{ $data['eyebrow'] ?? '' }}</p>
            <h2>{{ $data['title'] ?? '' }}</h2>
            <p>{{ $data['subtitle'] ?? '' }}</p>
        </div>

        <div class="ob-norisk-grid">
            @foreach($data['cards'] ?? [] as $i => $card)
                <div class="ob-norisk-card ob-reveal" style="--ob-i:{{ $i }}">
                    <div class="ob-mk">✕</div>
                    <h3>{{ $card['title'] ?? '' }}</h3>
                    <p>{{ $card['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>

        @if(!empty($data['note']))
            <p class="ob-norisk-note ob-reveal">{!! $data['note'] !!}</p>
        @endif
    </div>
</section>
