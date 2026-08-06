<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\CodeEditor;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use OursBlanc\Xms\Blocks\Block;

class CodeBlock extends Block
{
    public static function name(): string
    {
        return 'code';
    }

    public static function label(): string
    {
        return 'Code';
    }

    public static function description(): string
    {
        return 'Raw HTML injected as-is — for embeds, widgets, or markup no other block covers.';
    }

    public static function fields(): array
    {
        return [
            CodeEditor::make('code')
                ->label('HTML')
                ->language(Language::Html)
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.code';
    }
}
