@props(['mediaId' => null, 'alt' => '', 'sizes' => '100vw', 'class' => null])

@php
    $media = $mediaId ? \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId) : null;
@endphp

@if($media)
    <picture {{ $attributes->merge(['class' => $class]) }}>
        <source
            type="image/webp"
            srcset="{{ $media->getUrl('w480') }} 480w, {{ $media->getUrl('w960') }} 960w, {{ $media->getUrl('w1920') }} 1920w"
            sizes="{{ $sizes }}"
        >
        <img src="{{ $media->getUrl() }}" alt="{{ $alt }}" loading="lazy">
    </picture>
@endif
