<section class="ob-section ob-section--dark">
    <div class="ob-wrap">
        <div class="ob-spec-sheet ob-reveal">
            @if(!empty($data['eyebrow']))
                <p class="ob-eyebrow ob-eyebrow--dk">{{ $data['eyebrow'] }}</p>
            @endif

            @foreach($data['specs'] ?? [] as $spec)
                <div class="ob-spec-row">
                    <span class="label">{{ $spec['label'] ?? '' }}</span>
                    <span class="value">{{ $spec['value'] ?? '' }}</span>
                </div>
            @endforeach

            @if(!empty($data['tags']))
                <div class="ob-spec-tags-block">
                    <span class="label">{{ __('Tags') }}</span>
                    <div class="ob-spec-tags">
                        @foreach($data['tags'] as $tag)
                            <span>{{ $tag['name'] ?? '' }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
