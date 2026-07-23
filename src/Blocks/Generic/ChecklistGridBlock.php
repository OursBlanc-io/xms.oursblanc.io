<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class ChecklistGridBlock extends Block
{
    public static function name(): string
    {
        return 'checklist-grid';
    }

    public static function label(): string
    {
        return 'Checklist Grid';
    }

    public static function description(): string
    {
        return 'Eyebrow, title, subtitle and a grid of icon + title + text cards, plus a closing note.';
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
            Repeater::make('cards')
                ->schema([
                    TextInput::make('title')->required(),
                    TextInput::make('text')->required(),
                ])
                ->minItems(1)
                ->maxItems(4)
                ->required(),
            TextInput::make('note')
                ->label('Closing note (HTML <b> allowed)'),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.checklist-grid';
    }
}
