<?php

namespace OursBlanc\Xms\Filament\Resources\PageResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;
use OursBlanc\Xms\Blocks\BuilderStateTransformer;
use OursBlanc\Xms\Filament\Resources\PageResource;
use OursBlanc\Xms\Models\Page;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->publishAction(),
            $this->unpublishAction(),
            $this->duplicateAction(),
            $this->historyAction(),
            $this->previewAction(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['blocks'] = BuilderStateTransformer::toBuilderState($data['blocks'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['blocks'] = BuilderStateTransformer::toStoredState($data['blocks'] ?? []);

        return $data;
    }

    protected function publishAction(): Action
    {
        return Action::make('publish')
            ->label('Publish')
            ->color('success')
            ->visible(fn () => $this->record->status !== 'published')
            ->requiresConfirmation()
            ->action(function () {
                $this->record->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                $this->refreshFormData(['status', 'published_at']);

                Notification::make()->title('Page published')->success()->send();
            });
    }

    protected function unpublishAction(): Action
    {
        return Action::make('unpublish')
            ->label('Unpublish')
            ->color('gray')
            ->visible(fn () => $this->record->status === 'published')
            ->requiresConfirmation()
            ->action(function () {
                $this->record->update(['status' => 'draft']);

                $this->refreshFormData(['status']);

                Notification::make()->title('Page unpublished')->success()->send();
            });
    }

    protected function duplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicate to locale')
            ->icon('heroicon-o-document-duplicate')
            ->schema([
                Select::make('locale')
                    ->label('Target locale')
                    ->options(fn () => collect(config('xms.locales'))
                        ->diff($this->record->translationGroup->pages()->pluck('locale'))
                        ->mapWithKeys(fn (string $locale) => [$locale => $locale]))
                    ->required(),
            ])
            ->action(function (array $data) {
                $duplicate = Page::create([
                    'translation_group_id' => $this->record->translation_group_id,
                    'locale' => $data['locale'],
                    'slug' => $this->record->slug,
                    'title' => $this->record->title,
                    'blocks' => $this->record->blocks,
                    'seo' => $this->record->seo,
                    'template' => $this->record->template,
                    'status' => 'draft',
                ]);

                Notification::make()->title('Page duplicated')->success()->send();

                $this->redirect(static::getResource()::getUrl('edit', ['record' => $duplicate]));
            });
    }

    protected function previewAction(): Action
    {
        return Action::make('preview')
            ->label('Preview')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn () => URL::temporarySignedRoute(
                'xms.preview',
                now()->addMinutes(30),
                ['page' => $this->record->id],
            ))
            ->openUrlInNewTab();
    }

    protected function historyAction(): Action
    {
        return Action::make('history')
            ->label('History')
            ->icon('heroicon-o-clock')
            ->modalHeading('Revision history')
            ->modalContent(fn () => view('xms::filament.revision-history', [
                'page' => $this->record,
                'revisions' => $this->record->revisions()->get(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    public function restoreRevision(int $revisionId): void
    {
        $revision = $this->record->revisions()->findOrFail($revisionId);

        $this->record->update([
            'title' => $revision->title,
            'slug' => $revision->slug,
            'blocks' => $revision->blocks,
            'seo' => $revision->seo,
        ]);

        $this->fillForm();

        Notification::make()->title('Revision restored')->success()->send();

        $this->dispatch('close-modal', id: 'history');
    }
}
