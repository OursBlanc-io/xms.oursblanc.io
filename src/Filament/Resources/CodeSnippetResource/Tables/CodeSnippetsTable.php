<?php

namespace OursBlanc\Xms\Filament\Resources\CodeSnippetResource\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use OursBlanc\Xms\Models\CodeSnippet;

class CodeSnippetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('placement')
                    ->badge()
                    ->sortable(),
                TextColumn::make('locale')
                    ->badge()
                    ->placeholder('All locales')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('placement')
                    ->options([
                        CodeSnippet::PLACEMENT_HEAD => 'Head',
                        CodeSnippet::PLACEMENT_BODY_START => 'Body start',
                        CodeSnippet::PLACEMENT_BODY_END => 'Body end',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }
}
