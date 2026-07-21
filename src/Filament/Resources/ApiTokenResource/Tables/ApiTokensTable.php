<?php

namespace OursBlanc\Xms\Filament\Resources\ApiTokenResource\Tables;

use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApiTokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('abilities')
                    ->badge()
                    ->separator(','),
                TextColumn::make('last_used_at')
                    ->dateTime()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Revoke'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
