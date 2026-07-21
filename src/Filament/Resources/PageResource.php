<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\PageResource\Pages\CreatePage;
use OursBlanc\Xms\Filament\Resources\PageResource\Pages\EditPage;
use OursBlanc\Xms\Filament\Resources\PageResource\Pages\ListPages;
use OursBlanc\Xms\Filament\Resources\PageResource\Schemas\PageForm;
use OursBlanc\Xms\Filament\Resources\PageResource\Tables\PagesTable;
use OursBlanc\Xms\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
