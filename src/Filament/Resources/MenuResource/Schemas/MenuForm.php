<?php

namespace OursBlanc\Xms\Filament\Resources\MenuResource\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use OursBlanc\Xms\Models\Page;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->helperText('Admin-only label, e.g. "Header navigation".'),
            TextInput::make('location')
                ->required()
                ->maxLength(255)
                ->helperText('Free-form key used by the theme to fetch this menu, e.g. "header" or "footer".')
                ->unique(
                    table: 'xms_menus',
                    column: 'location',
                    ignoreRecord: true,
                    modifyRuleUsing: fn ($rule, $get) => $rule->where('locale', $get('locale')),
                ),
            Select::make('locale')
                ->options(array_combine(config('xms.locales'), config('xms.locales')))
                ->required(),
            Repeater::make('items')
                ->label('Menu items')
                ->schema(static::itemFields(withChildren: true))
                ->addActionLabel('Add item')
                ->collapsible()
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array<int, Component>
     */
    protected static function itemFields(bool $withChildren): array
    {
        $fields = [
            TextInput::make('label')
                ->required()
                ->maxLength(255),
            Select::make('link_type')
                ->label('Link')
                ->options([
                    'page' => 'Internal page',
                    'url' => 'Custom URL / anchor',
                ])
                ->default('url')
                ->live()
                ->required(),
            Select::make('page_id')
                ->label('Page')
                ->options(fn (Get $get) => Page::query()
                    ->when($get('../../locale'), fn ($query, $locale) => $query->where('locale', $locale))
                    ->orderBy('title')
                    ->pluck('title', 'id'))
                ->searchable()
                ->visible(fn (Get $get) => $get('link_type') === 'page'),
            TextInput::make('url')
                ->label('URL / anchor')
                ->helperText('e.g. #formats or https://...')
                ->visible(fn (Get $get) => $get('link_type') !== 'page'),
        ];

        if ($withChildren) {
            $fields[] = Repeater::make('children')
                ->label('Sub-items')
                ->schema(static::itemFields(withChildren: false))
                ->addActionLabel('Add sub-item')
                ->collapsible();
        }

        return $fields;
    }
}
