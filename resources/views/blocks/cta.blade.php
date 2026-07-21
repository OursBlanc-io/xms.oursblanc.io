<div class="xms-block xms-block--cta" data-style="{{ $data['style'] ?? 'primary' }}">
    <h3 class="xms-block__title">{{ $data['title'] ?? '' }}</h3>
    @if(!empty($data['text']))
        <p class="xms-block__text">{{ $data['text'] }}</p>
    @endif
    <a class="xms-block__button" href="{{ $data['button_url'] ?? '#' }}">{{ $data['button_label'] ?? '' }}</a>
</div>
