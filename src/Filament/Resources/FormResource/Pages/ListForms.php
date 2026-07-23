<?php

namespace OursBlanc\Xms\Filament\Resources\FormResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use OursBlanc\Xms\Filament\Resources\FormResource;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
