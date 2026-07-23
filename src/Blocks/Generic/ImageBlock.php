<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;
use OursBlanc\Xms\Filament\Forms\Components\PexelsPicker;

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
            PageMediaUpload::make('image')
                ->image()
                ->required()
                ->hintAction(PexelsPicker::image('image', 'alt', 'attribution', 'attribution_url')),
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
            Hidden::make('attribution'),
            Hidden::make('attribution_url'),
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
