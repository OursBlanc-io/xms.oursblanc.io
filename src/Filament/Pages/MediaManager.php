<?php

namespace OursBlanc\Xms\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use OursBlanc\Xms\Media\MediaManagerService;
use OursBlanc\Xms\Support\MediaManagerPath;

class MediaManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $navigationLabel = 'Media Manager';

    protected string $view = 'xms::filament.pages.media-manager';

    /**
     * Current sub-path being browsed, relative to the media manager's root
     * (e.g. "brand/logos") — kept in sync with the URL so a folder can be
     * bookmarked/shared. Always re-sanitized on write (see mount()/browseTo()),
     * never trusted as-is: this is client-controlled state.
     */
    #[Url]
    public string $path = '';

    public function mount(): void
    {
        $this->path = MediaManagerPath::sanitize($this->path);
    }

    public function getTitle(): string
    {
        return 'Media Manager';
    }

    protected function service(): MediaManagerService
    {
        return app(MediaManagerService::class);
    }

    public function breadcrumbs(): array
    {
        return $this->service()->breadcrumbs($this->path);
    }

    public function browseTo(string $path): void
    {
        $this->path = MediaManagerPath::sanitize($path);
        $this->resetTable();
    }

    public function browseUp(): void
    {
        $segments = explode('/', $this->path);
        array_pop($segments);
        $this->browseTo(implode('/', array_filter($segments)));
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->records())
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->icon(fn (array $record): Heroicon => $record['type'] === 'folder'
                        ? Heroicon::OutlinedFolder
                        : Heroicon::OutlinedDocument)
                    ->iconColor(fn (array $record): string => $record['type'] === 'folder' ? 'primary' : 'gray')
                    ->searchable()
                    ->action(function (array $record): void {
                        if ($record['type'] === 'folder') {
                            $this->browseTo(MediaManagerPath::join($this->path, $record['name']));
                        }
                    }),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : number_format($state / 1024, 1).' KB'),
                TextColumn::make('last_modified')
                    ->label('Modified')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->recordActions([
                $this->viewFileUrlAction(),
                $this->renameFileAction(),
                $this->deleteFileAction(),
                $this->renameFolderAction(),
                $this->deleteFolderAction(),
            ]);
    }

    /**
     * @return Collection<string, array{type: string, name: string, size: ?int, url: ?string, last_modified: ?int}>
     */
    protected function records(): Collection
    {
        $folders = collect($this->service()->folders($this->path))
            ->map(fn (string $name): array => [
                'type' => 'folder',
                'name' => $name,
                'size' => null,
                'url' => null,
                'last_modified' => null,
            ]);

        $files = collect($this->service()->files($this->path))
            ->map(fn (array $file): array => ['type' => 'file', ...$file]);

        return $folders->concat($files)
            ->mapWithKeys(fn (array $record): array => ["{$record['type']}:{$record['name']}" => $record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createFolderAction(),
            $this->uploadAction(),
        ];
    }

    protected function createFolderAction(): Action
    {
        return Action::make('createFolder')
            ->label('New folder')
            ->icon('heroicon-o-folder-plus')
            ->schema([
                TextInput::make('name')
                    ->label('Folder name')
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[^\/\\\\]+$/')
                    ->helperText('No slashes.'),
            ])
            ->action(function (array $data) {
                if (! $this->service()->createFolder($this->path, $data['name'])) {
                    Notification::make()->danger()->title('Could not create the folder (name already taken?).')->send();

                    return;
                }

                Notification::make()->success()->title('Folder created')->send();
                $this->resetTable();
            });
    }

    protected function uploadAction(): Action
    {
        return Action::make('upload')
            ->label('Upload files')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('files')
                    ->label('Files')
                    ->multiple()
                    ->disk(fn () => config('xms.media_manager.disk'))
                    ->directory(fn () => $this->service()->fullPath($this->path))
                    ->visibility('public')
                    ->preserveFilenames()
                    ->required(),
            ])
            ->action(function () {
                Notification::make()->success()->title('Files uploaded')->send();
                $this->resetTable();
            });
    }

    protected function viewFileUrlAction(): Action
    {
        return Action::make('viewFileUrl')
            ->label('URL')
            ->icon('heroicon-o-link')
            ->color('gray')
            ->visible(fn (array $record): bool => $record['type'] === 'file')
            ->modalHeading(fn (array $record): string => $record['name'])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->schema(fn (array $record): array => [
                TextInput::make('url')
                    ->label('File URL')
                    ->default($record['url'])
                    ->readOnly()
                    ->copyable(),
            ]);
    }

    protected function renameFileAction(): Action
    {
        return Action::make('renameFile')
            ->label('Rename')
            ->icon('heroicon-o-pencil')
            ->visible(fn (array $record): bool => $record['type'] === 'file')
            ->schema(fn (array $record): array => [
                TextInput::make('name')
                    ->label('New name')
                    ->default($record['name'])
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[^\/\\\\]+$/'),
            ])
            ->action(function (array $data, array $record) {
                if (! $this->service()->renameFile($this->path, $record['name'], $data['name'])) {
                    Notification::make()->danger()->title('Could not rename the file (name already taken?).')->send();

                    return;
                }

                Notification::make()->success()->title('File renamed')->send();
                $this->resetTable();
            });
    }

    protected function deleteFileAction(): Action
    {
        return Action::make('deleteFile')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (array $record): bool => $record['type'] === 'file')
            ->requiresConfirmation()
            ->action(function (array $record) {
                $this->service()->deleteFile($this->path, $record['name']);

                Notification::make()->success()->title('File deleted')->send();
                $this->resetTable();
            });
    }

    protected function renameFolderAction(): Action
    {
        return Action::make('renameFolder')
            ->label('Rename')
            ->icon('heroicon-o-pencil')
            ->visible(fn (array $record): bool => $record['type'] === 'folder')
            ->schema(fn (array $record): array => [
                TextInput::make('name')
                    ->label('New name')
                    ->default($record['name'])
                    ->required()
                    ->maxLength(255)
                    ->regex('/^[^\/\\\\]+$/'),
            ])
            ->action(function (array $data, array $record) {
                if (! $this->service()->renameFolder($this->path, $record['name'], $data['name'])) {
                    Notification::make()->danger()->title('Could not rename the folder (name already taken?).')->send();

                    return;
                }

                Notification::make()->success()->title('Folder renamed')->send();
                $this->resetTable();
            });
    }

    protected function deleteFolderAction(): Action
    {
        return Action::make('deleteFolder')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn (array $record): bool => $record['type'] === 'folder')
            ->requiresConfirmation()
            ->modalDescription('This deletes the folder and everything inside it.')
            ->action(function (array $record) {
                $this->service()->deleteFolder($this->path, $record['name']);

                Notification::make()->success()->title('Folder deleted')->send();
                $this->resetTable();
            });
    }
}
