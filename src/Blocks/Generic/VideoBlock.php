<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class VideoBlock extends Block
{
    public static function name(): string
    {
        return 'video';
    }

    public static function label(): string
    {
        return 'Video';
    }

    public static function description(): string
    {
        return 'An uploaded video or a YouTube/Vimeo URL, with an optional poster image.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('video')
                ->numeric(),
            TextInput::make('url')
                ->url(),
            TextInput::make('poster')
                ->numeric(),
        ];
    }

    public static function mediaFields(): array
    {
        return ['video', 'poster'];
    }

    public static function view(): string
    {
        return 'xms::blocks.video';
    }
}
