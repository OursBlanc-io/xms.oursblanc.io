@php
    $trustLogos = $data['trust_logos'] ?? [];
@endphp

<section class="ob-hero ob-breathe">
    <div class="ob-hero-fx">
        <div class="ob-mesh">
            <span class="a1"></span>
            <span class="a2"></span>
            <span class="a3"></span>
            <span class="a4"></span>
        </div>
        <div class="ob-ribbons">
            <div class="ob-ribbon r1"></div>
            <div class="ob-ribbon r2"></div>
        </div>
        <canvas class="ob-particles"></canvas>
        <div class="ob-grid-bg"></div>
    </div>

    <div class="ob-wrap ob-hero-inner">
        <p class="ob-eyebrow ob-eyebrow--dk" style="animation:ob-wordup .85s var(--ob-ease) .5s forwards;opacity:0">
            {{ $data['eyebrow'] ?? '' }}
        </p>

        <h1>
            @foreach(preg_split('/\s+/', trim($data['title_lead'] ?? '')) as $i => $word)
                <span class="ob-w" style="animation-delay:{{ .05 + $i * .1 }}s">{{ $word }}</span>
            @endforeach
            <br>
            @foreach(preg_split('/\s+/', trim($data['title_accent'] ?? '')) as $i => $word)
                <span class="ob-w ob-accent" style="animation-delay:{{ .4 + $i * .1 }}s">{{ $word }}</span>
            @endforeach
        </h1>

        <p class="ob-lede">{{ $data['lede'] ?? '' }}</p>

        <div class="ob-hero-cta">
            @if(!empty($data['cta_primary_label']) && !empty($data['cta_primary_url']))
                <a href="{{ $data['cta_primary_url'] }}" class="ob-btn ob-btn--primary">
                    {{ $data['cta_primary_label'] }} <span class="ob-btn__arrow">→</span>
                </a>
            @endif
            @if(!empty($data['cta_secondary_label']) && !empty($data['cta_secondary_url']))
                <a href="{{ $data['cta_secondary_url'] }}" class="ob-btn ob-btn--ghost">
                    {{ $data['cta_secondary_label'] }}
                </a>
            @endif
        </div>

        @if(!empty($data['trust_label']) && count($trustLogos))
            <div class="ob-trust">
                <p class="ob-trust-label">{{ $data['trust_label'] }}</p>
                <div class="ob-marquee">
                    <div class="ob-marquee-track">
                        @foreach($trustLogos as $logo)
                            <span>{{ $logo['name'] ?? '' }}</span>
                        @endforeach
                        @foreach($trustLogos as $logo)
                            <span>{{ $logo['name'] ?? '' }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
