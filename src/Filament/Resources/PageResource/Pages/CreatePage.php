<?php

namespace OursBlanc\Xms\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use OursBlanc\Xms\Blocks\BuilderStateTransformer;
use OursBlanc\Xms\Filament\Resources\PageResource;
use OursBlanc\Xms\Media\PageMediaSynchronizer;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * `illustration` isn't a real column (it's synced into a media
     * collection in afterCreate(), same hand-off as block media) — it must
     * be pulled out of $data before Page::create(), which would otherwise
     * silently ignore it anyway since it isn't fillable, but stashing it
     * here keeps the two steps explicit.
     */
    protected ?string $pendingIllustration = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['blocks'] = BuilderStateTransformer::toStoredState($data['blocks'] ?? []);
        $data['seo'] ??= [];
        $data['slug'] ??= '';

        $this->pendingIllustration = $data['illustration'] ?? null;
        unset($data['illustration']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(PageMediaSynchronizer::class)->sync($this->record);
        app(PageMediaSynchronizer::class)->syncIllustration($this->record, $this->pendingIllustration);
    }
}
