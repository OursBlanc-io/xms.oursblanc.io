@php
    use OursBlanc\Xms\Support\PageUrlGenerator;
@endphp
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($groups as $group)
    @foreach($group as $page)
    <url>
        <loc>{{ PageUrlGenerator::for($page) }}</loc>
        <lastmod>{{ $page->updated_at->toAtomString() }}</lastmod>
        @foreach($group as $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $alternate->locale }}" href="{{ PageUrlGenerator::for($alternate) }}" />
        @endforeach
    </url>
    @endforeach
@endforeach
</urlset>
