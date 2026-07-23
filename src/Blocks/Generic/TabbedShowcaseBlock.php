<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class TabbedShowcaseBlock extends Block
{
    /**
     * The interactive device demo animation is a fixed visual per key —
     * only the label/description copy is editable from the admin.
     */
    public const DEMO_KEYS = ['pulse', 'solar', 'cover', 'view', 'read', 'skin', 'max', 'x'];

    public static function name(): string
    {
        return 'tabbed-showcase';
    }

    public static function label(): string
    {
        return 'Tabbed Showcase';
    }

    public static function description(): string
    {
        return 'Interactive tab list paired with a live device demo, one entry per tab.';
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
            TextInput::make('anchor_id')
                ->label('Anchor ID (optional)')
                ->helperText("For in-page links, e.g. 'showcase'."),
            Repeater::make('formats')
                ->schema([
                    Select::make('demo')
                        ->options(array_combine(self::DEMO_KEYS, self::DEMO_KEYS))
                        ->required(),
                    TextInput::make('title')
                        ->required(),
                    TextInput::make('description')
                        ->required(),
                ])
                ->minItems(1)
                ->maxItems(8)
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.tabbed-showcase';
    }
}
