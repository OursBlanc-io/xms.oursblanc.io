<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\MenuResource\Pages\CreateMenu;
use OursBlanc\Xms\Filament\Resources\MenuResource\Pages\EditMenu;
use OursBlanc\Xms\Filament\Resources\MenuResource\Pages\ListMenus;
use OursBlanc\Xms\Filament\Resources\MenuResource\Schemas\MenuForm;
use OursBlanc\Xms\Filament\Resources\MenuResource\Tables\MenusTable;
use OursBlanc\Xms\Models\Menu;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
