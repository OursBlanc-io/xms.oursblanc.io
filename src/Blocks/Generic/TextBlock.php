<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\MarkdownEditor;
use OursBlanc\Xms\Blocks\Block;

class TextBlock extends Block
{
    public static function name(): string
    {
        return 'text';
    }

    public static function label(): string
    {
        return 'Text';
    }

    public static function description(): string
    {
        return 'A block of markdown-formatted text.';
    }

    public static function fields(): array
    {
        return [
            MarkdownEditor::make('content')
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.text';
    }
}
