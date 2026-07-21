<figure class="xms-block xms-block--image" data-width="{{ $data['width'] ?? 'content' }}">
    @if(!empty($data['image']))
        <x-xms::picture :media-id="$data['image']" :alt="$data['alt'] ?? ''" />
    @endif
    @if(!empty($data['caption']))
        <figcaption class="xms-block__caption">{{ $data['caption'] }}</figcaption>
    @endif
</figure>
