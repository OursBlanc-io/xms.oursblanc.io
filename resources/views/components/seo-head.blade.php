@php
    use OursBlanc\Xms\Support\PageUrlGenerator;

    $seo = $page->seo ?? [];
    $title = $seo['title'] ?? $page->title;
    $canonical = $seo['canonical'] ?? PageUrlGenerator::for($page);

    $alternates = $page->translationGroup
        ? $page->translationGroup->pages()->published()->get()->push($page)->unique('locale')
        : collect([$page]);
@endphp

<title>{{ $title }}</title>

@if(!empty($seo['description']))
    <meta name="description" content="{{ $seo['description'] }}">
@endif

<link rel="canonical" href="{{ $canonical }}">

@foreach($alternates as $alternate)
    <link rel="alternate" hreflang="{{ $alternate->locale }}" href="{{ PageUrlGenerator::for($alternate) }}">
@endforeach

<meta property="og:title" content="{{ $seo['og_title'] ?? $title }}">
@if(!empty($seo['og_description']))
    <meta property="og:description" content="{{ $seo['og_description'] }}">
@endif
<meta property="og:url" content="{{ $canonical }}">

@if(!empty($seo['robots']))
    <meta name="robots" content="{{ $seo['robots'] }}">
@endif

@if(!empty($seo['structured_data']))
    <script type="application/ld+json">{!! $seo['structured_data'] !!}</script>
@endif
