<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class FeatureGridBlock extends Block
{
    public static function name(): string
    {
        return 'feature-grid';
    }

    public static function label(): string
    {
        return 'Feature Grid';
    }

    public static function description(): string
    {
        return 'Eyebrow, title, subtitle and a grid of title + text cards.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('eyebrow')
                ->required(),
            TextInput::make('title')
                ->required(),
            TextInput::make('subtitle')
                ->required(),
            Repeater::make('items')
                ->schema([
                    TextInput::make('title')->required(),
                    TextInput::make('text')->required(),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()
                ->collapsed()
                ->minItems(1)
                ->maxItems(6)
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.feature-grid';
    }
}
