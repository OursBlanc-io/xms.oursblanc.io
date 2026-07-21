<?php

namespace OursBlanc\Xms\Filament\Resources\CodeSnippetResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use OursBlanc\Xms\Filament\Resources\CodeSnippetResource;

class ManageCodeSnippets extends ManageRecords
{
    protected static string $resource = CodeSnippetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
