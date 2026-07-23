<?php

namespace OursBlanc\Xms\Filament\Resources\ApiTokenResource\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApiTokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            CheckboxList::make('abilities')
                ->options([
                    'pages:read' => 'Read pages',
                    'pages:write' => 'Write pages',
                    'pages:publish' => 'Publish pages',
                    'menus:read' => 'Read menus',
                    'menus:write' => 'Write menus',
                    'forms:read' => 'Read forms',
                    'forms:write' => 'Write forms',
                ])
                ->required(),
        ]);
    }
}
