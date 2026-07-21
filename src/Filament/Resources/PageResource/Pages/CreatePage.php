<?php

namespace OursBlanc\Xms\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use OursBlanc\Xms\Blocks\BuilderStateTransformer;
use OursBlanc\Xms\Filament\Resources\PageResource;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['blocks'] = BuilderStateTransformer::toStoredState($data['blocks'] ?? []);
        $data['seo'] ??= [];

        return $data;
    }
}
