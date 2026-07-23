<section @if(!empty($data['anchor_id'])) id="{{ $data['anchor_id'] }}" @endif class="ob-section ob-section--frost">
    <div class="ob-wrap ob-plat-inner">
        <div class="ob-reveal">
            <p class="ob-eyebrow">{{ $data['eyebrow'] ?? '' }}</p>
            <h2>{{ $data['title'] ?? '' }}</h2>
            <div class="ob-dsp-cloud" style="margin-top:30px">
                @foreach($data['tags'] ?? [] as $tag)
                    <span>{{ $tag['name'] ?? '' }}</span>
                @endforeach
            </div>
        </div>

        <div class="ob-plat-points">
            @foreach($data['points'] ?? [] as $i => $point)
                <div class="ob-plat-point ob-reveal" style="--ob-i:{{ $i }}">
                    <div class="ob-ic">{{ $point['icon'] ?? '' }}</div>
                    <div>
                        <h3>{{ $point['title'] ?? '' }}</h3>
                        <p>{{ $point['text'] ?? '' }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
