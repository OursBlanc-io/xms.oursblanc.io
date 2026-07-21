<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class ColumnsBlock extends Block
{
    public static function name(): string
    {
        return 'columns';
    }

    public static function label(): string
    {
        return 'Columns';
    }

    public static function description(): string
    {
        return 'Two or three columns, each with a title, markdown text and an optional image.';
    }

    public static function fields(): array
    {
        return [
            Repeater::make('columns')
                ->schema([
                    TextInput::make('title'),
                    MarkdownEditor::make('content'),
                    TextInput::make('image')
                        ->numeric(),
                ])
                ->minItems(2)
                ->maxItems(3)
                ->required(),
        ];
    }

    public static function mediaFields(): array
    {
        return ['columns.*.image'];
    }

    public static function view(): string
    {
        return 'xms::blocks.columns';
    }
}
