<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class TaggedHighlightsBlock extends Block
{
    public static function name(): string
    {
        return 'tagged-highlights';
    }

    public static function label(): string
    {
        return 'Tagged Highlights';
    }

    public static function description(): string
    {
        return 'A cloud of tag pills next to a list of highlight points (icon, title, text).';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('eyebrow')
                ->required(),
            TextInput::make('title')
                ->required(),
            TextInput::make('anchor_id')
                ->label('Anchor ID (optional)')
                ->helperText("For in-page links, e.g. 'platform'."),
            Repeater::make('tags')
                ->schema([
                    TextInput::make('name')->required(),
                ])
                ->minItems(1)
                ->required(),
            Repeater::make('points')
                ->schema([
                    TextInput::make('icon')
                        ->label('Icon (single glyph)')
                        ->required(),
                    TextInput::make('title')->required(),
                    TextInput::make('text')->required(),
                ])
                ->minItems(1)
                ->maxItems(4)
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.tagged-highlights';
    }
}
