<?php

namespace OursBlanc\Xms\Support;

use OursBlanc\Xms\Models\Page;

class PageUrlGenerator
{
    public static function for(Page $page): string
    {
        return url(static::path($page->locale, $page->slug));
    }

    public static function path(?string $locale, string $slug): string
    {
        $slug = ltrim($slug, '/');

        if ($locale === null || ! config('xms.locale_in_url')) {
            return '/'.$slug;
        }

        $isDefaultHidden = $locale === config('xms.default_locale') && config('xms.default_locale_hidden');

        $prefix = $isDefaultHidden ? '' : "/{$locale}";

        return rtrim("{$prefix}/{$slug}", '/') ?: '/';
    }
}
