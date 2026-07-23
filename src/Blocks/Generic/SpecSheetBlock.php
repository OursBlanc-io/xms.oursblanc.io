<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class SpecSheetBlock extends Block
{
    public static function name(): string
    {
        return 'spec-sheet';
    }

    public static function label(): string
    {
        return 'Spec Sheet';
    }

    public static function description(): string
    {
        return 'A translucent card of label/value spec rows on a dark background, plus optional tag badges.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('eyebrow'),
            Repeater::make('specs')
                ->schema([
                    TextInput::make('label')->required(),
                    TextInput::make('value')->required(),
                ])
                ->minItems(1)
                ->required(),
            Repeater::make('tags')
                ->schema([
                    TextInput::make('name')->required(),
                ]),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.spec-sheet';
    }
}
