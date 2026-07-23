<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class CtaBannerBlock extends Block
{
    public static function name(): string
    {
        return 'cta-banner';
    }

    public static function label(): string
    {
        return 'CTA Banner';
    }

    public static function description(): string
    {
        return 'Centered call to action banner on a breathing dark background.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('title')
                ->required(),
            TextInput::make('subtitle')
                ->required(),
            TextInput::make('cta_label')
                ->required(),
            TextInput::make('cta_url')
                ->regex('/^(#[^\s]*|\/[^\s]*|https?:\/\/[^\s]+)$/')
                ->required(),
            TextInput::make('anchor_id')
                ->label('Anchor ID (optional)')
                ->helperText("For in-page links, e.g. 'contact'."),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.cta-banner';
    }
}
