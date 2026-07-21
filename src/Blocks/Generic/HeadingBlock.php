<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class HeadingBlock extends Block
{
    public static function name(): string
    {
        return 'heading';
    }

    public static function label(): string
    {
        return 'Heading';
    }

    public static function description(): string
    {
        return 'Section heading, level h1 to h4, with an optional anchor.';
    }

    public static function fields(): array
    {
        return [
            Select::make('level')
                ->options([
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                ])
                ->default('h2')
                ->required(),
            TextInput::make('text')
                ->required(),
            TextInput::make('anchor'),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.heading';
    }
}
