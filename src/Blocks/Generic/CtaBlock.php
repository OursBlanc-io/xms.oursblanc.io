<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class CtaBlock extends Block
{
    public static function name(): string
    {
        return 'cta';
    }

    public static function label(): string
    {
        return 'Call to action';
    }

    public static function description(): string
    {
        return 'A title, text and a labeled button linking to a URL.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('title')
                ->required(),
            TextInput::make('text'),
            TextInput::make('button_label')
                ->required(),
            TextInput::make('button_url')
                ->regex('/^(#[^\s]*|\/[^\s]*|https?:\/\/[^\s]+)$/')
                ->required(),
            Select::make('style')
                ->options([
                    'primary' => 'Primary',
                    'secondary' => 'Secondary',
                    'outline' => 'Outline',
                ])
                ->default('primary')
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.cta';
    }
}
