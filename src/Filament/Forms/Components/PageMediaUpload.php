<?php

namespace OursBlanc\Xms\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A thin FileUpload wrapper used for every media field across generic and
 * custom blocks. It intentionally does NOT attach to the media library
 * itself: on submit it just stores the upload under a pending directory on
 * the configured disk and dehydrates to that path (a string). The page not
 * having an id yet on create (and the current block's uuid living in a
 * sibling hidden field inside a Builder item, not reachable in a stable way
 * from here) makes attaching here fragile; OursBlanc\Xms\Media\PageMediaSynchronizer
 * instead turns every pending path into a real Media model — keyed by the
 * block's own uuid — once the Page has been persisted, from an
 * afterCreate()/afterSave() Filament hook.
 *
 * That hand-off is exactly why this field's state isn't always a pending
 * disk path: once synced, it becomes a Media id (an int). Filament's own
 * FileUpload doesn't know about that second shape at all — its default
 * hydration silently drops any state that isn't a real path in `getDisk()`
 * (built for the "always a path" case), which without the overrides below
 * makes an already-saved image/video vanish from the field the moment the
 * form is reloaded, even though the Media attachment itself is intact.
 */
class PageMediaUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->disk(fn () => config('xms.media_disk'));
        $this->directory('xms-pending');
        $this->visibility('public');

        // Filament's default 'compact' panel is a plain filename/size row —
        // no visual preview at all for images or video. 'grid' is what
        // actually renders a thumbnail (or a playable video tile).
        $this->panelLayout('grid');

        // Without a cap, an uploaded video renders at its native height in
        // the admin panel, which for a portrait/vertical clip can dwarf the
        // rest of the form. Despite the name, this option also bounds video
        // tiles (filepond-plugin-media-preview reads it as `mediaPreviewHeight`).
        $this->imagePreviewHeight('12rem');

        // Skips the built-in `Storage::disk()->exists($state)` filter run on
        // every hydration: with a Media id in state, that check is always
        // false (there's no file literally named e.g. "42" on disk) and
        // Filament quietly empties the field before it ever reaches
        // getUploadedFileUsing() below.
        $this->fetchFileInformation(false);

        $this->getUploadedFileUsing(function (self $component, string $file): ?array {
            if (ctype_digit($file)) {
                $media = Media::find((int) $file);

                return $media ? [
                    'name' => $media->file_name,
                    'size' => $media->size,
                    'type' => $media->mime_type,
                    'url' => $media->getUrl(),
                ] : null;
            }

            $disk = $component->getDisk();

            if (! $disk->exists($file)) {
                return null;
            }

            return [
                'name' => basename($file),
                'size' => $disk->size($file),
                'type' => $disk->mimeType($file),
                'url' => $disk->url($file),
            ];
        });
    }

    /**
     * Resolves a field's raw state (either a pending disk path or, once
     * synced, a Media id) to a public URL — shared with the video block's
     * own preview, which doesn't rely on Filament's built-in FileUpload
     * thumbnail (see PexelsPicker/VideoBlock: filepond-plugin-media-preview
     * doesn't reliably paint a first frame for reloaded server-side videos).
     */
    public static function resolveUrl(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (ctype_digit((string) $value)) {
            return Media::find((int) $value)?->getUrl();
        }

        $disk = Storage::disk(config('xms.media_disk'));

        return $disk->exists($value) ? $disk->url($value) : null;
    }

    /**
     * A plain <img>, as a companion `Placeholder` next to the field (see any
     * block's use of `imagePreviewHtml()` below) — FilePond's own preview
     * plugin doesn't reliably render a thumbnail for a file reloaded from
     * the server (same limitation as VideoBlock::previewHtml() works around
     * for video), so this is a guaranteed-correct fallback built directly
     * from the resolved URL instead.
     */
    public static function imagePreviewHtml(mixed $value, string $maxHeight = '10rem'): ?Htmlable
    {
        $url = static::resolveUrl($value);

        if (! $url) {
            return null;
        }

        return new HtmlString(
            '<img src="'.e($url).'" alt="" style="max-height: '.e($maxHeight).'; max-width: 100%; border-radius: 0.5rem; display: block;">'
        );
    }
}
