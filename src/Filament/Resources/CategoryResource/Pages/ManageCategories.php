<?php

namespace OursBlanc\Xms\Filament\Resources\CategoryResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use OursBlanc\Xms\Filament\Resources\CategoryResource;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
