<div class="xms-block xms-block--video">
    {{-- Media rendering via <x-xms::video> lands in Phase 4 --}}
    @if(!empty($data['url']))
        <a href="{{ $data['url'] }}" class="xms-block__video-link">{{ $data['url'] }}</a>
    @endif
</div>
