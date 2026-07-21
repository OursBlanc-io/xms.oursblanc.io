<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class GalleryBlock extends Block
{
    public static function name(): string
    {
        return 'gallery';
    }

    public static function label(): string
    {
        return 'Gallery';
    }

    public static function description(): string
    {
        return 'A repeatable list of images displayed in a configurable number of columns.';
    }

    public static function fields(): array
    {
        return [
            Repeater::make('images')
                ->schema([
                    TextInput::make('image')
                        ->numeric()
                        ->required(),
                    TextInput::make('alt'),
                ]),
            Select::make('columns')
                ->options([
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ])
                ->default('3')
                ->required(),
        ];
    }

    public static function mediaFields(): array
    {
        return ['images.*.image'];
    }

    public static function view(): string
    {
        return 'xms::blocks.gallery';
    }
}
