@props(['mediaId' => null, 'posterId' => null, 'url' => null, 'class' => null])

@php
    $media = $mediaId ? \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId) : null;
    $poster = $posterId ? \Spatie\MediaLibrary\MediaCollections\Models\Media::find($posterId) : null;
@endphp

@if($media)
    <video {{ $attributes->merge(['class' => $class]) }} controls @if($poster) poster="{{ $poster->getUrl() }}" @endif>
        <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
    </video>
@elseif($url)
    <div class="xms-block__video-embed">
        <iframe src="{{ $url }}" loading="lazy" allowfullscreen></iframe>
    </div>
@endif
