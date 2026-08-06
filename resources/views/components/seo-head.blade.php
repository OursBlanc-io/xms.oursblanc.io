@php
    use OursBlanc\Xms\Support\PageUrlGenerator;
    use Spatie\MediaLibrary\MediaCollections\Models\Media;

    $seo = $page->seo ?? [];
    $title = $seo['title'] ?? $page->title;
    $canonical = $seo['canonical'] ?? PageUrlGenerator::for($page);
    $siteName = config('xms.seo.site_name');

    $localeMap = ['fr' => 'fr_FR', 'en' => 'en_US'];
    $ogLocale = $localeMap[$page->locale] ?? $localeMap[config('xms.default_locale')] ?? null;

    $ogImage = null;
    if (! empty($seo['og_image_media_id'])) {
        $ogImage = Media::find($seo['og_image_media_id'])?->getUrl();
    }
    $ogImage ??= config('xms.seo.default_og_image');

    // A locale-agnostic page (no locale) has no hreflang identity of its
    // own and isn't part of any translation set — skip alternates entirely
    // rather than emit an invalid hreflang="".
    $alternates = ($page->translationGroup && $page->locale)
        ? $page->translationGroup->pages()->published()->get()->push($page)->unique('locale')->filter(fn ($p) => $p->locale)
        : collect();
@endphp

<title>{{ $title }}</title>

@if(!empty($seo['description']))
    <meta name="description" content="{{ $seo['description'] }}">
@endif

<link rel="canonical" href="{{ $canonical }}">

@foreach($alternates as $alternate)
    <link rel="alternate" hreflang="{{ $alternate->locale }}" href="{{ PageUrlGenerator::for($alternate) }}">
@endforeach

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $seo['og_title'] ?? $title }}">
@if(!empty($seo['og_description']) || !empty($seo['description']))
    <meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
@if($siteName)
    <meta property="og:site_name" content="{{ $siteName }}">
@endif
@if($ogLocale)
    <meta property="og:locale" content="{{ $ogLocale }}">
@endif
@if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif

<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['og_title'] ?? $title }}">
@if(!empty($seo['og_description']) || !empty($seo['description']))
    <meta name="twitter:description" content="{{ $seo['og_description'] ?? $seo['description'] }}">
@endif
@if($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif
@if(config('xms.seo.twitter_handle'))
    <meta name="twitter:site" content="{{ config('xms.seo.twitter_handle') }}">
@endif

@if(!empty($seo['robots']))
    <meta name="robots" content="{{ $seo['robots'] }}">
@endif

@if(config('xms.seo.organization_name'))
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@'.'context' => 'https://schema.org',
        '@'.'type' => 'Organization',
        'name' => config('xms.seo.organization_name'),
        'url' => url('/'),
        'logo' => config('xms.seo.organization_logo'),
        'sameAs' => config('xms.seo.organization_same_as') ?: null,
    ])) !!}</script>
@endif

@if(!empty($seo['structured_data']))
    <script type="application/ld+json">{!! is_array($seo['structured_data']) ? json_encode($seo['structured_data']) : $seo['structured_data'] !!}</script>
@endif
