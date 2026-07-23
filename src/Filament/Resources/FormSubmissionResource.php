<?php

namespace OursBlanc\Xms\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use OursBlanc\Xms\Filament\Resources\FormSubmissionResource\Pages\ListFormSubmissions;
use OursBlanc\Xms\Filament\Resources\FormSubmissionResource\Tables\FormSubmissionsTable;
use OursBlanc\Xms\Models\FormSubmission;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $modelLabel = 'Form submission';

    public static function table(Table $table): Table
    {
        return FormSubmissionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
        ];
    }
}
