<section class="ob-section ob-section--light">
    <div class="ob-wrap">
        <div class="ob-section-head ob-reveal">
            <p class="ob-eyebrow">{{ $data['eyebrow'] ?? '' }}</p>
            <h2>{{ $data['title'] ?? '' }}</h2>
            <p>{{ $data['subtitle'] ?? '' }}</p>
        </div>

        <div class="ob-cov-grid">
            @foreach($data['items'] ?? [] as $i => $item)
                <div class="ob-cov ob-reveal" style="--ob-i:{{ $i }}">
                    <h3>{{ $item['title'] ?? '' }}</h3>
                    <p>{{ $item['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
