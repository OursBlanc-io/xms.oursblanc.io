<?php

namespace OursBlanc\Xms\Filament\Resources\ConfigurationResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ConfigurationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('key')
                ->required()
                ->maxLength(255)
                ->helperText('Machine key a block reads this configuration by, e.g. "campaign-simulator-oursblanc-rates".')
                ->unique(table: 'xms_configurations', column: 'key', ignoreRecord: true),
            TextInput::make('label')
                ->maxLength(255)
                ->helperText('Admin-only, for identifying this configuration in the list.'),
            Textarea::make('value')
                ->label('Value (JSON)')
                ->required()
                ->rows(16)
                ->extraInputAttributes(['style' => 'font-family: ui-monospace, monospace; font-size: 0.85rem;'])
                ->afterStateHydrated(fn ($component, $state) => $component->state(
                    is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $state
                ))
                ->dehydrateStateUsing(fn ($state) => json_decode((string) $state, true))
                ->rule(function () {
                    return function (string $attribute, mixed $value, \Closure $fail) {
                        json_decode((string) $value);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            $fail('Invalid JSON: '.json_last_error_msg());
                        }
                    };
                }),
        ]);
    }
}
