<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;
use OursBlanc\Xms\Filament\Forms\Components\PexelsPicker;

class GalleryBlock extends Block
{
    public static function name(): string
    {
        return 'gallery';
    }

    public static function label(): string
    {
        return 'Gallery';
    }

    public static function description(): string
    {
        return 'A repeatable list of images displayed in a configurable number of columns.';
    }

    public static function fields(): array
    {
        return [
            Repeater::make('images')
                ->schema([
                    PageMediaUpload::make('image')
                        ->image()
                        ->required()
                        ->hintAction(PexelsPicker::image('image', 'alt', 'attribution', 'attribution_url')),
                    Placeholder::make('image_preview')
                        ->hiddenLabel()
                        ->visible(fn (Get $get) => filled($get('image')))
                        ->content(fn (Get $get) => PageMediaUpload::imagePreviewHtml($get('image'))),
                    TextInput::make('alt'),
                    Hidden::make('attribution'),
                    Hidden::make('attribution_url'),
                ])
                ->itemLabel(fn (array $state): ?string => $state['alt'] ?? null)
                ->collapsible()
                ->collapsed(),
            Select::make('columns')
                ->options([
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ])
                ->default('3')
                ->required(),
        ];
    }

    public static function mediaFields(): array
    {
        return ['images.*.image'];
    }

    public static function view(): string
    {
        return 'xms::blocks.gallery';
    }
}
