<div class="xms-block xms-block--hero" data-alignment="{{ $data['alignment'] ?? 'left' }}" data-style="{{ $data['style'] ?? 'boxed-dark' }}">
    @if(!empty($data['image']))
        <x-xms::picture :media-id="$data['image']" :alt="$data['title'] ?? ''" class="xms-block__background" />
    @endif
    <div class="xms-block__content">
        <h1 class="xms-block__title">{{ $data['title'] ?? '' }}</h1>
        @if(!empty($data['subtitle']))
            <p class="xms-block__subtitle">{{ $data['subtitle'] }}</p>
        @endif
        @if(!empty($data['cta_label']) && !empty($data['cta_url']))
            <a class="xms-block__cta" href="{{ $data['cta_url'] }}">{{ $data['cta_label'] }}</a>
        @endif
    </div>
</div>
