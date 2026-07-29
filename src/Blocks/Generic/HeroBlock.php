<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;
use OursBlanc\Xms\Filament\Forms\Components\PexelsPicker;

class HeroBlock extends Block
{
    public static function name(): string
    {
        return 'hero';
    }

    public static function label(): string
    {
        return 'Hero';
    }

    public static function description(): string
    {
        return 'Full-width banner with a title, subtitle, background image and an optional call to action.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('title')
                ->required(),
            TextInput::make('subtitle'),
            PageMediaUpload::make('image')
                ->image()
                ->hintAction(PexelsPicker::image('image', attributionField: 'attribution', attributionUrlField: 'attribution_url')),
            Placeholder::make('image_preview')
                ->hiddenLabel()
                ->visible(fn (Get $get) => filled($get('image')))
                ->content(fn (Get $get) => PageMediaUpload::imagePreviewHtml($get('image'))),
            TextInput::make('cta_label'),
            TextInput::make('cta_url')
                ->regex('/^(#[^\s]*|\/[^\s]*|https?:\/\/[^\s]+)$/'),
            Select::make('alignment')
                ->options([
                    'left' => 'Left',
                    'center' => 'Center',
                    'right' => 'Right',
                ])
                ->default('left')
                ->required(),
            Select::make('style')
                ->options([
                    'boxed-dark' => 'Boxed — dark',
                    'boxed-light' => 'Boxed — light',
                    'plain-dark' => 'Plain — dark',
                    'plain-light' => 'Plain — light',
                ])
                ->default('boxed-dark')
                ->required(),
            Hidden::make('attribution'),
            Hidden::make('attribution_url'),
        ];
    }

    public static function mediaFields(): array
    {
        return ['image'];
    }

    public static function view(): string
    {
        return 'xms::blocks.hero';
    }
}
