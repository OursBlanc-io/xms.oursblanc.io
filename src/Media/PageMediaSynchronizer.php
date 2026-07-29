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
        $changed = $this->syncBlocks($page, $blocks);

        if ($changed) {
            $page->forceFill(['blocks' => $blocks])->saveQuietly();
        }

        $this->markOrphans($page->fresh());
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     */
    protected function syncBlocks(Page $page, array &$blocks): bool
    {
        $changed = false;

        foreach ($blocks as &$block) {
            $blockClass = $this->registry->find($block['type'] ?? '');

            if (! $blockClass) {
                continue;
            }

            foreach ($blockClass::mediaFields() as $mediaFieldPath) {
                $changed = $this->resolveMediaField($page, $block, $mediaFieldPath, $blockClass) || $changed;
            }

            foreach ($blockClass::nestedBlockFields() as $nestedPath) {
                $changed = $this->resolveNestedBlocks($page, $block, $nestedPath) || $changed;
            }
        }
        unset($block);

        return $changed;
    }

    /**
     * Recurses `syncBlocks()` into a nested-blocks field (see
     * Block::nestedBlockFields()) — each nested block gets its own media
     * synced under its own uuid, exactly like a top-level page block.
     */
    protected function resolveNestedBlocks(Page $page, array &$block, string $path): bool
    {
        if (! str_contains($path, '.*.')) {
            return isset($block['data'][$path]) && is_array($block['data'][$path])
                && $this->syncBlocks($page, $block['data'][$path]);
        }

        [$repeaterKey, $subField] = explode('.*.', $path, 2);
        $changed = false;

        if (isset($block['data'][$repeaterKey]) && is_array($block['data'][$repeaterKey])) {
            foreach ($block['data'][$repeaterKey] as &$item) {
                if (is_array($item) && isset($item[$subField]) && is_array($item[$subField])) {
                    $changed = $this->syncBlocks($page, $item[$subField]) || $changed;
                }
            }
            unset($item);
        }

        return $changed;
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

        // A field already synced to a Media id round-trips through the
        // browser/Livewire as a numeric *string* (e.g. after PageMediaUpload
        // re-displays it, per its own docblock), not the PHP int this method
        // originally wrote back — plain `is_string()` can no longer tell
        // "still pending" apart from "already attached". Without this check,
        // Storage::exists() on that id resolves true anyway (spatie stores
        // each media's file under a directory named after its own id, e.g.
        // "5/file.mp4", which Flysystem reports as an existing path), so this
        // would try to re-attach that directory as if it were the file
        // itself and crash retrieving its size. A pending upload path is
        // never purely numeric ("xms-pending/{uuid}.{ext}"), so this is safe.
        if (ctype_digit($value)) {
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
     * Same pending-path handoff as attachIfPending(), but for the page-level
     * illustration (a fixed, single-file collection — not block-scoped, so
     * it isn't reachable via `sync()`/mediaFields()). Call once the page has
     * a persisted id, passing the raw dehydrated value of the form's
     * `illustration` field (stripped out of $data before Page::create()/
     * update(), since it isn't a real column).
     */
    public function syncIllustration(Page $page, mixed $value): bool
    {
        if (! is_string($value) || $value === '' || ctype_digit($value)) {
            return false;
        }

        $disk = config('xms.media_disk');

        if (! Storage::disk($disk)->exists($value)) {
            return false;
        }

        $page->addMediaFromDisk($value, $disk)->toMediaCollection(Page::ILLUSTRATION_COLLECTION, $disk);

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
        $activeUuids = $this->collectBlockUuids($page->blocks ?? []);

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
     * Every block uuid in $blocks, recursing into nested-blocks fields (see
     * Block::nestedBlockFields()) so a nested block's media isn't wrongly
     * flagged as orphaned.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, string>
     */
    protected function collectBlockUuids(array $blocks): array
    {
        $uuids = [];

        foreach ($blocks as $block) {
            if (empty($block['uuid'])) {
                continue;
            }

            $uuids[] = $block['uuid'];
            $blockClass = $this->registry->find($block['type'] ?? '');

            foreach ($blockClass ? $blockClass::nestedBlockFields() : [] as $path) {
                $uuids = array_merge($uuids, $this->collectNestedUuids($block['data'] ?? [], $path));
            }
        }

        return $uuids;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    protected function collectNestedUuids(array $data, string $path): array
    {
        if (! str_contains($path, '.*.')) {
            return isset($data[$path]) && is_array($data[$path]) ? $this->collectBlockUuids($data[$path]) : [];
        }

        [$repeaterKey, $subField] = explode('.*.', $path, 2);
        $uuids = [];

        foreach ($data[$repeaterKey] ?? [] as $item) {
            if (is_array($item) && isset($item[$subField]) && is_array($item[$subField])) {
                $uuids = array_merge($uuids, $this->collectBlockUuids($item[$subField]));
            }
        }

        return $uuids;
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
