<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class ImageBlock extends Block
{
    public static function name(): string
    {
        return 'image';
    }

    public static function label(): string
    {
        return 'Image';
    }

    public static function description(): string
    {
        return 'A single image with alt text, caption and a width setting.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('image')
                ->numeric()
                ->required(),
            TextInput::make('alt'),
            TextInput::make('caption'),
            Select::make('width')
                ->options([
                    'content' => 'Content',
                    'large' => 'Large',
                    'full' => 'Full width',
                ])
                ->default('content')
                ->required(),
        ];
    }

    public static function mediaFields(): array
    {
        return ['image'];
    }

    public static function view(): string
    {
        return 'xms::blocks.image';
    }
}
