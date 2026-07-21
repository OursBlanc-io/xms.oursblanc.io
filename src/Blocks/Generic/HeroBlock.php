<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;

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
                ->image(),
            TextInput::make('cta_label'),
            TextInput::make('cta_url')
                ->url(),
            Select::make('alignment')
                ->options([
                    'left' => 'Left',
                    'center' => 'Center',
                    'right' => 'Right',
                ])
                ->default('left')
                ->required(),
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
