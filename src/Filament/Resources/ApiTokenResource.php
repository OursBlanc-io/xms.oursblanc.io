<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\ApiTokenResource\Pages\ManageApiTokens;
use OursBlanc\Xms\Filament\Resources\ApiTokenResource\Schemas\ApiTokenForm;
use OursBlanc\Xms\Filament\Resources\ApiTokenResource\Tables\ApiTokensTable;
use OursBlanc\Xms\Models\ApiToken;

class ApiTokenResource extends Resource
{
    protected static ?string $model = ApiToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $modelLabel = 'API token';

    public static function form(Schema $schema): Schema
    {
        return ApiTokenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiTokensTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageApiTokens::route('/'),
        ];
    }
}
