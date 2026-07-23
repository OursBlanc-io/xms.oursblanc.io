<?php

namespace OursBlanc\Xms\Filament\Resources\FormResource\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('fields_count')
                    ->label('Fields')
                    ->counts('fields'),
                TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions'),
                IconColumn::make('webhook_enabled')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
