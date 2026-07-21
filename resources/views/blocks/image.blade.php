<figure class="xms-block xms-block--image" data-width="{{ $data['width'] ?? 'content' }}">
    {{-- Media rendering via <x-xms::picture> lands in Phase 4 --}}
    @if(!empty($data['caption']))
        <figcaption class="xms-block__caption">{{ $data['caption'] }}</figcaption>
    @endif
</figure>
