<?php

namespace OursBlanc\Xms\Filament\Resources\FormSubmissionResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use OursBlanc\Xms\Models\Form;

class FormSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form.name')
                    ->label('Form')
                    ->sortable(),
                TextColumn::make('ip_address'),
                TextColumn::make('created_at')
                    ->label('Submitted at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('form_id')
                    ->label('Form')
                    ->options(fn () => Form::query()->pluck('name', 'id')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->modalHeading('Submission data')
                    ->modalContent(fn ($record) => view('xms::filament.submission-data', ['data' => $record->data]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
