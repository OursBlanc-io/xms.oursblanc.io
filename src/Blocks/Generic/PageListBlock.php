<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Models\Category;
use OursBlanc\Xms\Models\Page;

class PageListBlock extends Block
{
    public static function name(): string
    {
        return 'page_list';
    }

    public static function label(): string
    {
        return 'Page list';
    }

    public static function description(): string
    {
        return 'Lists published pages, optionally filtered by category and/or freeform metadata facets '
            .'(e.g. a blog index, or a filterable format-demo gallery), with pagination.';
    }

    public static function fields(): array
    {
        return [
            Select::make('category')
                ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'slug'))
                ->searchable()
                ->placeholder('All categories'),
            TagsInput::make('facets')
                ->label('Filterable metadata keys')
                ->placeholder('e.g. format, visual_type')
                ->helperText('Page `meta` keys to expose as filter dropdowns on this listing (values are gathered from the pages themselves).'),
            TextInput::make('per_page')
                ->numeric()
                ->default(10)
                ->minValue(1)
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.page-list';
    }

    public static function resolveData(array $data, Page $page): array
    {
        $perPage = max(1, (int) ($data['per_page'] ?? 10));
        $facetKeys = array_values(array_filter($data['facets'] ?? []));

        $baseQuery = fn () => static::baseQuery($page, $data);

        $data['facet_filters'] = collect($facetKeys)->map(function (string $key) use ($baseQuery) {
            $selected = request()->query($key);

            return [
                'key' => $key,
                'label' => Str::headline($key),
                // Plucking a JSON path directly (e.g. "meta->format") trips
                // over how differently each driver aliases the extracted
                // column; pulling the whole `meta` array back and reading
                // the key in PHP sidesteps that entirely.
                'options' => $baseQuery()
                    ->pluck('meta')
                    ->map(fn (?array $meta) => $meta[$key] ?? null)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
                'selected' => is_string($selected) ? $selected : null,
            ];
        })->all();

        $query = $baseQuery();

        foreach ($data['facet_filters'] as $facet) {
            if ($facet['selected'] !== null && $facet['selected'] !== '') {
                $query->whereMeta($facet['key'], $facet['selected']);
            }
        }

        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        // withQueryString() so page links keep the active facet filters —
        // otherwise clicking to page 2 would silently drop them.
        $data['pages'] = $query->paginate($perPage, ['*'], 'page', $currentPage)->withQueryString();

        return $data;
    }

    protected static function baseQuery(Page $page, array $data): Builder
    {
        $query = Page::query()
            ->published()
            ->locale($page->locale)
            ->where('id', '!=', $page->id)
            ->orderByDesc('published_at');

        if (! empty($data['category'])) {
            $query->inCategory($data['category']);
        }

        return $query;
    }
}
