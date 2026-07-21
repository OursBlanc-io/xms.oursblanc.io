<?php

namespace OursBlanc\Xms\Filament\Resources\CodeSnippetResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use OursBlanc\Xms\Models\CodeSnippet;

class CodeSnippetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->helperText('Admin-only label, e.g. "Google Tag Manager".'),
            Select::make('placement')
                ->options([
                    CodeSnippet::PLACEMENT_HEAD => 'Head (before </head>)',
                    CodeSnippet::PLACEMENT_BODY_START => 'Body start (right after <body>)',
                    CodeSnippet::PLACEMENT_BODY_END => 'Body end (before </body>)',
                ])
                ->required(),
            Select::make('locale')
                ->label('Locale')
                ->options(array_combine(config('xms.locales'), config('xms.locales')))
                ->placeholder('All locales')
                ->helperText('Leave empty to run on every locale.'),
            Textarea::make('code')
                ->label('HTML / script')
                ->required()
                ->rows(10)
                ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace; font-size: 0.85rem;'])
                ->helperText('Raw HTML, printed as-is. Only grant panel access to trusted editors.')
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->default(true),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->helperText('Lower runs first within the same placement.'),
        ]);
    }
}
