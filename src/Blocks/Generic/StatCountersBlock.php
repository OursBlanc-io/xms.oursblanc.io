<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use OursBlanc\Xms\Blocks\Block;

class StatCountersBlock extends Block
{
    public static function name(): string
    {
        return 'stat-counters';
    }

    public static function label(): string
    {
        return 'Stat Counters';
    }

    public static function description(): string
    {
        return 'A grid of animated count-up stats next to a short copy block, on a dark background.';
    }

    public static function fields(): array
    {
        return [
            Repeater::make('stats')
                ->schema([
                    TextInput::make('value')
                        ->numeric()
                        ->required(),
                    TextInput::make('prefix'),
                    TextInput::make('suffix'),
                    TextInput::make('label')
                        ->required(),
                ])
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                ->collapsible()
                ->collapsed()
                ->minItems(1)
                ->maxItems(4)
                ->required(),
            TextInput::make('eyebrow')
                ->required(),
            TextInput::make('anchor_id')
                ->label('Anchor ID (optional)')
                ->helperText("For in-page links, e.g. 'stats'."),
            Repeater::make('paragraphs')
                ->schema([
                    Textarea::make('text')->required(),
                ])
                ->itemLabel(fn (array $state): ?string => isset($state['text']) ? Str::limit($state['text'], 40) : null)
                ->collapsible()
                ->collapsed()
                ->minItems(1)
                ->maxItems(4)
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.stat-counters';
    }
}
