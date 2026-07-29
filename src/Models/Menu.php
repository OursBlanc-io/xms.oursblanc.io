<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OursBlanc\Xms\Support\PageUrlGenerator;

class Menu extends Model
{
    protected $table = 'xms_menus';

    protected $fillable = [
        'location',
        'locale',
        'name',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function scopeLocation(Builder $query, string $location): Builder
    {
        return $query->where('location', $location);
    }

    public function scopeLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public static function forLocation(string $location, string $locale): ?self
    {
        return static::query()->location($location)->locale($locale)->first();
    }

    /**
     * Items with each entry (and its children, one level deep) enriched
     * with a resolved `resolved_url`, so Blade views never need to know
     * about link_type/page_id. Pass the page currently being rendered so
     * `language_switch` items can resolve to its translated sibling.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolvedItems(?Page $currentPage = null): array
    {
        return array_map(
            fn (array $item) => static::resolveItem($item, $currentPage),
            $this->items ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resolveItem(array $item, ?Page $currentPage): array
    {
        $item['resolved_url'] = static::resolveUrl($item, $currentPage);
        $item['children'] = array_map(
            fn (array $child) => static::resolveItem($child, $currentPage),
            $item['children'] ?? [],
        );

        return $item;
    }

    protected static function resolveUrl(array $item, ?Page $currentPage): ?string
    {
        $linkType = $item['link_type'] ?? 'url';

        if ($linkType === 'page' && ! empty($item['page_id'])) {
            $page = Page::find($item['page_id']);

            return $page ? PageUrlGenerator::for($page) : null;
        }

        if ($linkType === 'language_switch' && ! empty($item['target_locale'])) {
            return static::resolveLanguageSwitchUrl($item['target_locale'], $currentPage);
        }

        return $item['url'] ?? null;
    }

    /**
     * The current page's sibling in `$targetLocale` (same translation
     * group) when it's published, otherwise that locale's homepage.
     */
    protected static function resolveLanguageSwitchUrl(string $targetLocale, ?Page $currentPage): string
    {
        $sibling = $currentPage?->translationGroup
            ?->pages()
            ->where('locale', $targetLocale)
            ->published()
            ->first();

        if ($sibling) {
            return PageUrlGenerator::for($sibling);
        }

        $default = config('xms.default_locale');
        $hideDefault = config('xms.default_locale_hidden');

        return ($hideDefault && $targetLocale === $default) ? '/' : "/{$targetLocale}";
    }
}
