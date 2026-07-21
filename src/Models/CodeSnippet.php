<?php

namespace OursBlanc\Xms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CodeSnippet extends Model
{
    /**
     * Where a snippet is printed in the theme layout.
     */
    public const PLACEMENT_HEAD = 'head';

    public const PLACEMENT_BODY_START = 'body_start';

    public const PLACEMENT_BODY_END = 'body_end';

    protected $table = 'xms_code_snippets';

    protected $fillable = [
        'name',
        'placement',
        'locale',
        'code',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePlacement(Builder $query, string $placement): Builder
    {
        return $query->where('placement', $placement);
    }

    /**
     * A null `locale` means the snippet runs on every locale.
     */
    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('locale')->orWhere('locale', $locale));
    }

    /**
     * @return Collection<int, static>
     */
    public static function forPlacement(string $placement, string $locale): Collection
    {
        return static::query()
            ->active()
            ->placement($placement)
            ->forLocale($locale)
            ->orderBy('sort_order')
            ->get();
    }
}
