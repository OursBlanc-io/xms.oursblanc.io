@php($level = $data['level'] ?? 'h2')
<{{ $level }} class="xms-block xms-block--heading" @if(!empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    {{ $data['text'] ?? '' }}
</{{ $level }}>
