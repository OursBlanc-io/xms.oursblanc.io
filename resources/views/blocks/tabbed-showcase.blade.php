@php
    $formats = $data['formats'] ?? [];
    $first = $formats[0]['demo'] ?? 'pulse';
@endphp

<section @if(!empty($data['anchor_id'])) id="{{ $data['anchor_id'] }}" @endif class="ob-section ob-section--dark">
    <div class="ob-grid-bg"></div>
    <div class="ob-wrap" style="position:relative;z-index:2">
        <div class="ob-section-head ob-reveal">
            <p class="ob-eyebrow ob-eyebrow--dk">{{ $data['eyebrow'] ?? '' }}</p>
            <h2>{{ $data['title'] ?? '' }}</h2>
            <p>{{ $data['subtitle'] ?? '' }}</p>
        </div>

        <div class="ob-player ob-reveal">
            <div class="ob-fmt-list" role="tablist">
                @foreach($formats as $i => $format)
                    <button
                        class="ob-fmt-btn @if($i === 0) ob-on @endif"
                        data-fmt="{{ $format['demo'] }}"
                        role="tab"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    >
                        <h3>{{ $format['title'] ?? '' }}</h3>
                        <p>{{ $format['description'] ?? '' }}</p>
                    </button>
                @endforeach
            </div>

            <div class="ob-stage-wrap">
                <span class="ob-stage-badge">{{ $data['live_label'] ?? 'Aperçu live' }}</span>
                <div class="ob-device">
                    <div class="ob-device-bar"><i></i><i></i><i></i></div>
                    <div class="ob-screen">
                        <div class="ob-page-mock">
                            <div class="ob-ph-nav"></div>
                            <div class="ob-ph-img"></div>
                            <div class="ob-ph-line m"></div>
                            <div class="ob-ph-line"></div>
                            <div class="ob-ph-line s"></div>
                            <div class="ob-ph-line m"></div>
                            <div class="ob-ph-line"></div>
                        </div>

                        <div class="ob-stage" data-on="{{ $first }}">
                            <div class="ob-demo ob-d-pulse">
                                <div class="ob-signal"></div>
                                <div class="ob-hero-card"><b>Votre marque, révélée</b><small>Découvrir →</small></div>
                                <div class="ob-xtra">Xtra ›</div>
                            </div>
                            <div class="ob-demo ob-d-solar">
                                <div class="ob-glow"></div>
                                <div class="ob-banner"><b>Une présence qui marque les esprits</b></div>
                            </div>
                            <div class="ob-demo ob-d-cover">
                                <div class="ob-scroller">
                                    <div class="ob-ph-line m"></div>
                                    <div class="ob-ph-line"></div>
                                    <div class="ob-ph-line s"></div>
                                    <div class="ob-ph-line m"></div>
                                    <div class="ob-ph-line"></div>
                                    <div class="ob-ph-line s"></div>
                                </div>
                                <div class="ob-cover-ad">SmartCover</div>
                            </div>
                            <div class="ob-demo ob-d-view">
                                <div class="ob-view-el">SmartView</div>
                            </div>
                            <div class="ob-demo ob-d-read">
                                <div class="ob-read-block">SmartRead in-article</div>
                            </div>
                            <div class="ob-demo ob-d-skin">
                                <div class="ob-sk ob-sk-l"></div>
                                <div class="ob-sk ob-sk-r"></div>
                                <div class="ob-sk ob-sk-t"></div>
                            </div>
                            <div class="ob-demo ob-d-max">
                                <div class="ob-max-ov"><span class="ob-close">✕</span><b>SmartMax</b></div>
                            </div>
                            <div class="ob-demo ob-d-x">
                                <div class="ob-xitem ob-x1">SmartPulse</div>
                                <div class="ob-xitem ob-x2">SmartCover</div>
                                <div class="ob-xitem ob-x3">SmartView</div>
                                <div class="ob-xitem ob-x4">SmartSolar</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
