@props([
    'mediaId' => null,
    'posterId' => null,
    'url' => null,
    'class' => null,
    'autoplay' => false,
    'sound' => false,
    'controls' => true,
    'contentFit' => 'cover',
])

@php
    $media = $mediaId ? \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId) : null;
    $poster = $posterId ? \Spatie\MediaLibrary\MediaCollections\Models\Media::find($posterId) : null;

    // Browsers refuse to autoplay a video with sound: muted always wins over
    // the "sound" setting whenever autoplay is on.
    $muted = $autoplay || ! $sound;
@endphp

@if($media)
    <video
        {{ $attributes->merge(['class' => $class])->style(["object-fit: {$contentFit}"]) }}
        @if($controls) controls @endif
        @if($autoplay) autoplay loop @endif
        @if($muted) muted @endif
        playsinline
        @if($poster) poster="{{ $poster->getUrl() }}" @endif
    >
        <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
    </video>
@elseif($url)
    <div class="xms-block__video-embed">
        <iframe src="{{ $url }}" loading="lazy" allowfullscreen></iframe>
    </div>
@endif
