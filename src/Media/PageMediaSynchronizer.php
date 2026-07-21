<?php

namespace OursBlanc\Xms\Media;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Models\Page;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PageMediaSynchronizer
{
    public function __construct(protected BlockRegistry $registry) {}

    /**
     * Turn every pending upload path left in the page's blocks into a real
     * media attachment, then mark (or unmark) orphaned collections for
     * deferred deletion. Call once the page has a persisted id.
     */
    public function sync(Page $page): void
    {
        $blocks = $page->blocks ?? [];
        $changed = false;

        foreach ($blocks as &$block) {
            $blockClass = $this->registry->find($block['type'] ?? '');

            if (! $blockClass) {
                continue;
            }

            foreach ($blockClass::mediaFields() as $mediaFieldPath) {
                $changed = $this->resolveMediaField($page, $block, $mediaFieldPath, $blockClass) || $changed;
            }
        }
        unset($block);

        if ($changed) {
            $page->forceFill(['blocks' => $blocks])->saveQuietly();
        }

        $this->markOrphans($page->fresh());
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  class-string<Block>  $blockClass
     */
    protected function resolveMediaField(Page $page, array &$block, string $path, string $blockClass): bool
    {
        if (! str_contains($path, '.*.')) {
            return $this->attachIfPending($page, $block['data'], $path, $block['uuid'], $blockClass);
        }

        [$repeaterKey, $subField] = explode('.*.', $path, 2);
        $changed = false;

        // Note: `?? []` would evaluate to a temporary value and silently break
        // the reference below, so items are only iterated when the key is
        // genuinely set on the (by-reference) block data.
        if (isset($block['data'][$repeaterKey]) && is_array($block['data'][$repeaterKey])) {
            foreach ($block['data'][$repeaterKey] as &$item) {
                if (is_array($item)) {
                    $changed = $this->attachIfPending($page, $item, $subField, $block['uuid'], $blockClass) || $changed;
                }
            }
            unset($item);
        }

        return $changed;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  class-string<Block>  $blockClass
     */
    protected function attachIfPending(Page $page, array &$fields, string $key, string $blockUuid, string $blockClass): bool
    {
        $value = $fields[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return false;
        }

        $disk = config('xms.media_disk');

        if (! Storage::disk($disk)->exists($value)) {
            return false;
        }

        // addMediaFromDisk()'s $disk arg is only where the pending upload is
        // read FROM; toMediaCollection()'s own $diskName arg is what decides
        // where it's actually stored, defaulting to spatie's own
        // media-library.disk_name (not ours) if omitted.
        $media = $page->addMediaFromDisk($value, $disk)
            ->toMediaCollection("block-{$blockUuid}", $disk);

        $fields[$key] = $media->id;

        $this->maybeGeneratePoster($media, $fields, $key, $blockClass);

        return true;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  class-string<Block>  $blockClass
     */
    protected function maybeGeneratePoster(Media $media, array &$fields, string $key, string $blockClass): void
    {
        $posterField = $blockClass::posterFieldMap()[$key] ?? null;

        if (! $posterField || ! str_starts_with((string) $media->mime_type, 'video/')) {
            return;
        }

        if (! empty($fields[$posterField])) {
            return;
        }

        $poster = app(VideoProcessor::class)->generatePoster($media);

        if ($poster) {
            $fields[$posterField] = $poster->id;
        }
    }

    /**
     * Media whose block uuid no longer exists in the page's blocks gets a
     * 24h grace period (so draft back-and-forth survives) before deletion by
     * the scheduled prune command. A block that reappears clears the flag.
     */
    protected function markOrphans(Page $page): void
    {
        $activeUuids = collect($page->blocks ?? [])->pluck('uuid')->filter()->all();

        foreach ($page->media as $media) {
            if (! str_starts_with($media->collection_name, 'block-')) {
                continue;
            }

            $uuid = substr($media->collection_name, strlen('block-'));

            if (in_array($uuid, $activeUuids, true)) {
                if ($media->getCustomProperty('pending_deletion_at')) {
                    $media->forgetCustomProperty('pending_deletion_at');
                    $media->save();
                }

                continue;
            }

            if (! $media->getCustomProperty('pending_deletion_at')) {
                $media->setCustomProperty('pending_deletion_at', now()->addHours(24)->toIso8601String());
                $media->save();
            }
        }
    }

    /**
     * Permanently delete every media whose grace period has elapsed.
     * Intended to run on a schedule, across all pages.
     */
    public function pruneExpired(): int
    {
        $deleted = 0;

        Page::query()->with('media')->chunkById(50, function ($pages) use (&$deleted) {
            foreach ($pages as $page) {
                foreach ($page->media as $media) {
                    $pendingAt = $media->getCustomProperty('pending_deletion_at');

                    if ($pendingAt && now()->greaterThanOrEqualTo(Carbon::parse($pendingAt))) {
                        $media->delete();
                        $deleted++;
                    }
                }
            }
        });

        return $deleted;
    }
}
