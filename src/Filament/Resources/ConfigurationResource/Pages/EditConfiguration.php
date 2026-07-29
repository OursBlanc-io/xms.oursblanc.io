<?php

namespace OursBlanc\Xms\Filament\Resources\ConfigurationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use OursBlanc\Xms\Filament\Resources\ConfigurationResource;

class EditConfiguration extends EditRecord
{
    protected static string $resource = ConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
