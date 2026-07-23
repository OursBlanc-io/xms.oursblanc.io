<?php

namespace OursBlanc\Xms\Filament\Forms\Components;

use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use OursBlanc\Xms\Support\PexelsService;

/**
 * Builds the "Search Pexels" hint action attached to image/video upload
 * fields. Browsing (search + pagination) only ever touches Pexels-hosted
 * thumbnail URLs — nothing is downloaded or stored until a specific result
 * is picked and the action's own `action()` callback runs.
 *
 * The query/page fields deliberately aren't `live()`: reactivity on
 * keystroke doesn't trigger a request from inside an action's own modal
 * form nested inside another action (the block builder's edit action) — an
 * explicit "Search" button (a real Action click, the same mechanism that
 * opened this modal) is the only mechanism that reliably re-renders the
 * results grid here.
 */
class PexelsPicker
{
    public static function image(
        string $imageField,
        ?string $altField = null,
        ?string $attributionField = null,
        ?string $attributionUrlField = null,
    ): Action {
        return static::make(
            name: 'pickPexelsImage',
            label: 'Search Pexels',
            resultsView: 'xms::filament.forms.components.pexels-photo-grid',
            search: fn (string $query, int $page) => app(PexelsService::class)->searchPhotos($query, $page)['photos'],
            resolve: fn (int $id) => app(PexelsService::class)->resolveAndStorePhoto($id),
            apply: function (array $photo, Set $set) use ($imageField, $altField, $attributionField, $attributionUrlField) {
                $set($imageField, $photo['path']);

                if ($altField) {
                    $set($altField, $photo['alt']);
                }

                if ($attributionField) {
                    $set($attributionField, $photo['attribution']);
                }

                if ($attributionUrlField) {
                    $set($attributionUrlField, $photo['attribution_url']);
                }
            },
        );
    }

    public static function video(
        string $videoField,
        ?string $attributionField = null,
        ?string $attributionUrlField = null,
    ): Action {
        return static::make(
            name: 'pickPexelsVideo',
            label: 'Search Pexels videos',
            resultsView: 'xms::filament.forms.components.pexels-video-grid',
            search: fn (string $query, int $page) => app(PexelsService::class)->searchVideos($query, $page)['videos'],
            resolve: fn (int $id) => app(PexelsService::class)->resolveAndStoreVideo($id),
            apply: function (array $video, Set $set) use ($videoField, $attributionField, $attributionUrlField) {
                $set($videoField, $video['path']);

                if ($attributionField) {
                    $set($attributionField, $video['attribution']);
                }

                if ($attributionUrlField) {
                    $set($attributionUrlField, $video['attribution_url']);
                }
            },
        );
    }

    protected static function make(
        string $name,
        string $label,
        string $resultsView,
        \Closure $search,
        \Closure $resolve,
        \Closure $apply,
    ): Action {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-o-photo')
            ->visible(fn () => app(PexelsService::class)->enabled())
            ->modalHeading($label)
            ->modalSubmitActionLabel('Use this one')
            ->schema([
                TextInput::make('pexels_query')
                    ->hiddenLabel()
                    ->placeholder('Search Pexels…')
                    ->suffixAction(
                        Action::make('pexelsSearch')
                            ->label('Search')
                            ->icon('heroicon-o-magnifying-glass')
                            ->action(fn (Set $set) => $set('pexels_page', 1))
                    ),
                TextInput::make('pexels_page')
                    ->label('Page')
                    ->readOnly()
                    ->default(1)
                    ->prefixAction(
                        Action::make('pexelsPrevPage')
                            ->icon('heroicon-o-chevron-left')
                            ->action(fn (Set $set, Get $get) => $set('pexels_page', max(1, ((int) $get('pexels_page')) - 1)))
                    )
                    ->suffixAction(
                        Action::make('pexelsNextPage')
                            ->icon('heroicon-o-chevron-right')
                            ->action(fn (Set $set, Get $get) => $set('pexels_page', ((int) $get('pexels_page')) + 1))
                    ),
                // Hidden rather than Radio: Radio revalidates the submitted value
                // against options() re-evaluated live at submit time — a second
                // Pexels call that isn't guaranteed to return the same result set
                // (pagination/curated feeds drift), which fails validation
                // near-randomly. Hidden::required() only checks presence.
                Hidden::make('pexels_selected')
                    ->required()
                    ->columnSpanFull()
                    ->view($resultsView, fn (Get $get) => [
                        'results' => collect($search($get('pexels_query') ?? '', (int) ($get('pexels_page') ?? 1)))
                            ->keyBy('id')
                            ->all(),
                    ]),
            ])
            ->action(function (array $data, Set $set) use ($resolve, $apply) {
                $result = $resolve((int) ($data['pexels_selected'] ?? 0));

                if (! $result) {
                    Notification::make()->danger()->title('Failed to download from Pexels.')->send();

                    return;
                }

                $apply($result, $set);
            });
    }
}
