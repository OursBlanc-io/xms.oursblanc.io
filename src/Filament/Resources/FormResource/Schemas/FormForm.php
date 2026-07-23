<?php

namespace OursBlanc\Xms\Filament\Resources\FormResource\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('success_message')
                ->maxLength(255)
                ->helperText('Shown after a successful submission. Defaults to a generic thank-you message.'),
            TextInput::make('submit_label')
                ->label('Submit button label')
                ->maxLength(255)
                ->helperText('Defaults to "Submit".'),
            TagsInput::make('notification_emails')
                ->label('Notify by email')
                ->placeholder('Add an email and press enter')
                ->helperText('Every address here receives an email on each submission. Leave empty to disable.'),
            Toggle::make('webhook_enabled')
                ->live(),
            TextInput::make('webhook_url')
                ->label('Webhook URL')
                ->url()
                ->visible(fn (Get $get) => $get('webhook_enabled'))
                ->required(fn (Get $get) => $get('webhook_enabled')),
            Repeater::make('fields')
                ->relationship()
                ->schema([
                    TextInput::make('label')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('key', Str::slug($state ?? '', '_'))),
                    TextInput::make('key')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Machine name used as the submitted field name.'),
                    Select::make('type')
                        ->options([
                            'text' => 'Text',
                            'email' => 'Email',
                            'textarea' => 'Textarea',
                            'select' => 'Select',
                            'checkbox' => 'Checkbox',
                        ])
                        ->live()
                        ->required(),
                    TagsInput::make('options')
                        ->placeholder('Add an option and press enter')
                        ->visible(fn (Get $get) => $get('type') === 'select'),
                    Toggle::make('is_required'),
                ])
                ->columns(2)
                ->orderColumn('sort_order')
                ->collapsible()
                ->addActionLabel('Add field'),
        ]);
    }
}
