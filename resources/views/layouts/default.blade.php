<!doctype html>
<html lang="{{ $page->locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-xms::seo-head :page="$page" />
    <link rel="stylesheet" href="{{ asset('vendor/xms/xms.css') }}">
    @if(app(\OursBlanc\Xms\Rendering\ThemeManager::class)->assetEntries() !== [])
        @vite(app(\OursBlanc\Xms\Rendering\ThemeManager::class)->assetEntries())
    @endif
</head>
<body>
    <main class="xms-page" data-locale="{{ $page->locale }}" data-slug="{{ $page->slug }}">
        @foreach($blocks as $block)
            @if($block['view'])
                @include($block['view'], ['data' => $block['data'], 'page' => $page])
            @endif
        @endforeach
    </main>
    <script src="{{ asset('vendor/xms/xms.js') }}"></script>
</body>
</html>
