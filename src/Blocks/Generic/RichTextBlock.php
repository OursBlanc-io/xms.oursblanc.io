<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\RichEditor;
use OursBlanc\Xms\Blocks\Block;

class RichTextBlock extends Block
{
    public static function name(): string
    {
        return 'rich-text';
    }

    public static function label(): string
    {
        return 'Rich Text';
    }

    public static function description(): string
    {
        return 'A block of HTML content edited with a rich text (WYSIWYG) editor.';
    }

    public static function fields(): array
    {
        return [
            RichEditor::make('content')
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.rich-text';
    }
}
