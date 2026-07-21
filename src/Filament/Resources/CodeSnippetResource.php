<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\CodeSnippetResource\Pages\ManageCodeSnippets;
use OursBlanc\Xms\Filament\Resources\CodeSnippetResource\Schemas\CodeSnippetForm;
use OursBlanc\Xms\Filament\Resources\CodeSnippetResource\Tables\CodeSnippetsTable;
use OursBlanc\Xms\Models\CodeSnippet;

class CodeSnippetResource extends Resource
{
    protected static ?string $model = CodeSnippet::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    public static function form(Schema $schema): Schema
    {
        return CodeSnippetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CodeSnippetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCodeSnippets::route('/'),
        ];
    }
}
