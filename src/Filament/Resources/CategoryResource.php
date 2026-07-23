<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\CategoryResource\Pages\ManageCategories;
use OursBlanc\Xms\Filament\Resources\CategoryResource\Schemas\CategoryForm;
use OursBlanc\Xms\Filament\Resources\CategoryResource\Tables\CategoriesTable;
use OursBlanc\Xms\Models\Category;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
