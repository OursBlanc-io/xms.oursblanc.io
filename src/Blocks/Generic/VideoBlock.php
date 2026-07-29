<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Filament\Forms\Components\PageMediaUpload;
use OursBlanc\Xms\Filament\Forms\Components\PexelsPicker;

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
                ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/quicktime'])
                ->hintAction(PexelsPicker::video('video', 'attribution', 'attribution_url')),
            // Filament's own file preview doesn't reliably show a first frame
            // for a video reloaded from the server (a filepond-plugin-media-
            // preview limitation, not something fixable from here) — this is
            // a plain <video> pointed at the same URL, as a reliable backup.
            Placeholder::make('video_preview')
                ->hiddenLabel()
                ->visible(fn (Get $get) => filled($get('video')))
                ->content(fn (Get $get) => static::previewHtml($get('video'))),
            TextInput::make('url')
                ->url()
                ->helperText('YouTube/Vimeo URL, used if no video is uploaded above.'),
            PageMediaUpload::make('poster')
                ->image()
                ->helperText('Auto-generated from the video via ffmpeg if left empty.')
                ->hintAction(PexelsPicker::image('poster')),
            Placeholder::make('poster_preview')
                ->hiddenLabel()
                ->visible(fn (Get $get) => filled($get('poster')))
                ->content(fn (Get $get) => PageMediaUpload::imagePreviewHtml($get('poster'))),
            Toggle::make('autoplay')
                ->default(false)
                ->helperText('Browsers only allow autoplay when the video is also muted.'),
            Toggle::make('sound')
                ->label('Play with sound')
                ->default(false),
            Toggle::make('controls')
                ->label('Show player controls')
                ->default(true),
            Select::make('content_fit')
                ->label('Content fit')
                ->options([
                    'cover' => 'Cover (fills the space, may crop)',
                    'contain' => 'Contain (shows the whole frame, may letterbox)',
                    'fill' => 'Fill (stretches to the space)',
                ])
                ->default('cover')
                ->required(),
            Hidden::make('attribution'),
            Hidden::make('attribution_url'),
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

    protected static function previewHtml(mixed $value): ?Htmlable
    {
        $url = PageMediaUpload::resolveUrl($value);

        if (! $url) {
            return null;
        }

        return new HtmlString(
            '<video controls playsinline style="max-height: 12rem; max-width: 100%;" src="'.e($url).'"></video>'
        );
    }
}
