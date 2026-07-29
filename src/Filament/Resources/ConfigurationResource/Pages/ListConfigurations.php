<?php

namespace OursBlanc\Xms\Filament\Resources\ConfigurationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use OursBlanc\Xms\Filament\Resources\ConfigurationResource;

class ListConfigurations extends ListRecords
{
    protected static string $resource = ConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
