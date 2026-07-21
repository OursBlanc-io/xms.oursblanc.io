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
     * about link_type/page_id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolvedItems(): array
    {
        return array_map(
            fn (array $item) => static::resolveItem($item),
            $this->items ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function resolveItem(array $item): array
    {
        $item['resolved_url'] = static::resolveUrl($item);
        $item['children'] = array_map(
            fn (array $child) => static::resolveItem($child),
            $item['children'] ?? [],
        );

        return $item;
    }

    protected static function resolveUrl(array $item): ?string
    {
        if (($item['link_type'] ?? 'url') === 'page' && ! empty($item['page_id'])) {
            $page = Page::find($item['page_id']);

            return $page ? PageUrlGenerator::for($page) : null;
        }

        return $item['url'] ?? null;
    }
}
