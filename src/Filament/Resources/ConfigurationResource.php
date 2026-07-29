<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\ConfigurationResource\Pages\CreateConfiguration;
use OursBlanc\Xms\Filament\Resources\ConfigurationResource\Pages\EditConfiguration;
use OursBlanc\Xms\Filament\Resources\ConfigurationResource\Pages\ListConfigurations;
use OursBlanc\Xms\Filament\Resources\ConfigurationResource\Schemas\ConfigurationForm;
use OursBlanc\Xms\Filament\Resources\ConfigurationResource\Tables\ConfigurationsTable;
use OursBlanc\Xms\Models\Configuration;

class ConfigurationResource extends Resource
{
    protected static ?string $model = Configuration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function form(Schema $schema): Schema
    {
        return ConfigurationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConfigurationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConfigurations::route('/'),
            'create' => CreateConfiguration::route('/create'),
            'edit' => EditConfiguration::route('/{record}/edit'),
        ];
    }
}
