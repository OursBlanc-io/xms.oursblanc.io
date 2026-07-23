<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\FormResource\Pages\CreateForm;
use OursBlanc\Xms\Filament\Resources\FormResource\Pages\EditForm;
use OursBlanc\Xms\Filament\Resources\FormResource\Pages\ListForms;
use OursBlanc\Xms\Filament\Resources\FormResource\Schemas\FormForm;
use OursBlanc\Xms\Filament\Resources\FormResource\Tables\FormsTable;
use OursBlanc\Xms\Models\Form;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return FormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListForms::route('/'),
            'create' => CreateForm::route('/create'),
            'edit' => EditForm::route('/{record}/edit'),
        ];
    }
}
