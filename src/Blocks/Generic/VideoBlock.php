<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;

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
            PageMediaUpload::make('video')
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime']),
            TextInput::make('url')
                ->url()
                ->helperText('YouTube/Vimeo URL, used if no video is uploaded above.'),
            PageMediaUpload::make('poster')
                ->image()
                ->helperText('Auto-generated from the video via ffmpeg if left empty.'),
        ];
    }

    public static function mediaFields(): array
    {
        return ['video', 'poster'];
    }

    public static function posterFieldMap(): array
    {
        return ['video' => 'poster'];
    }

    public static function view(): string
    {
        return 'xms::blocks.video';
    }
}
