<?php

namespace OursBlanc\Xms\Support;

use Illuminate\Support\Str;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Models\Page;

/**
 * A true copy of a page: an independent draft the editor can freely change
 * without affecting the original — distinct from "duplicate to locale",
 * which creates a translation sibling sharing the same translation group.
 *
 * Block uuids are regenerated (they key media collections, see
 * PageMediaSynchronizer) and every block's media is physically copied —
 * reusing the source's Media rows would leave the duplicate's blocks
 * pointing at files that still belong to (and can be deleted from) the
 * original page.
 */
class PageDuplicator
{
    public function __construct(protected BlockRegistry $registry) {}

    public function duplicate(Page $page): Page
    {
        [$blocks, $uuidMap] = $this->remapBlockUuids($page->blocks ?? []);

        $duplicate = Page::create([
            // Left null: a plain copy isn't a translation of the original,
            // it gets its own independent translation group.
            'translation_group_id' => null,
            'locale' => $page->locale,
            'slug' => $this->uniqueSlug($page->locale, $page->slug),
            'title' => $page->title !== '' ? "{$page->title} (copy)" : $page->title,
            'list_title' => $page->list_title,
            'list_excerpt' => $page->list_excerpt,
            'blocks' => $blocks,
            'seo' => $page->seo,
            'meta' => $page->meta,
            'template' => $page->template,
            'status' => 'draft',
        ]);

        $duplicate->categories()->sync($page->categories()->allRelatedIds());

        $mediaIdMap = $this->copyBlockMedia($page, $duplicate, $uuidMap);

        if ($mediaIdMap !== []) {
            $duplicate->forceFill(['blocks' => $this->remapMediaIds($duplicate->blocks, $mediaIdMap)])->saveQuietly();
        }

        $this->copyIllustration($page, $duplicate);

        return $duplicate;
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, string>} [remapped blocks, old uuid => new uuid]
     */
    protected function remapBlockUuids(array $blocks): array
    {
        $uuidMap = [];

        $remapped = array_map(function (array $block) use (&$uuidMap) {
            $newUuid = (string) Str::uuid();
            $uuidMap[$block['uuid']] = $newUuid;
            $block['uuid'] = $newUuid;

            return $block;
        }, $blocks);

        return [$remapped, $uuidMap];
    }

    /**
     * Copies every media attached to a still-referenced block (skipping
     * anything already orphaned/pending deletion on the source) into the
     * matching collection on the duplicate.
     *
     * @param  array<string, string>  $uuidMap  old block uuid => new block uuid
     * @return array<int, int> old media id => new media id
     */
    protected function copyBlockMedia(Page $source, Page $duplicate, array $uuidMap): array
    {
        $mediaIdMap = [];

        foreach ($source->media as $media) {
            if (! str_starts_with($media->collection_name, 'block-')) {
                continue;
            }

            $oldUuid = substr($media->collection_name, strlen('block-'));
            $newUuid = $uuidMap[$oldUuid] ?? null;

            if (! $newUuid) {
                continue;
            }

            $copy = $media->copy($duplicate, "block-{$newUuid}", $media->disk);

            if ($copy->getCustomProperty('pending_deletion_at')) {
                $copy->forgetCustomProperty('pending_deletion_at');
                $copy->save();
            }

            $mediaIdMap[$media->id] = $copy->id;
        }

        return $mediaIdMap;
    }

    protected function copyIllustration(Page $source, Page $duplicate): void
    {
        $illustration = $source->getFirstMedia(Page::ILLUSTRATION_COLLECTION);

        $illustration?->copy($duplicate, Page::ILLUSTRATION_COLLECTION, $illustration->disk);
    }

    /**
     * Rewrites every block's media field values from the source's media ids
     * to the freshly copied ones, the same field paths (including
     * repeater `x.*.y` wildcards) PageMediaSynchronizer itself walks.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<int, int>  $mediaIdMap
     * @return array<int, array<string, mixed>>
     */
    protected function remapMediaIds(array $blocks, array $mediaIdMap): array
    {
        foreach ($blocks as &$block) {
            $blockClass = $this->registry->find($block['type'] ?? '');

            if (! $blockClass) {
                continue;
            }

            foreach ($blockClass::mediaFields() as $path) {
                if (! str_contains($path, '.*.')) {
                    $this->remapField($block['data'], $path, $mediaIdMap);

                    continue;
                }

                [$repeaterKey, $subField] = explode('.*.', $path, 2);

                if (isset($block['data'][$repeaterKey]) && is_array($block['data'][$repeaterKey])) {
                    foreach ($block['data'][$repeaterKey] as &$item) {
                        if (is_array($item)) {
                            $this->remapField($item, $subField, $mediaIdMap);
                        }
                    }
                    unset($item);
                }
            }
        }
        unset($block);

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<int, int>  $mediaIdMap
     */
    protected function remapField(array &$fields, string $key, array $mediaIdMap): void
    {
        $value = $fields[$key] ?? null;

        if (is_numeric($value) && isset($mediaIdMap[(int) $value])) {
            $fields[$key] = $mediaIdMap[(int) $value];
        }
    }

    protected function uniqueSlug(string $locale, string $slug): string
    {
        $base = $slug === '' ? 'copy' : "{$slug}-copy";
        $candidate = $base;
        $i = 2;

        while (Page::query()->where('locale', $locale)->where('slug', $candidate)->exists()) {
            $candidate = "{$base}-{$i}";
            $i++;
        }

        return $candidate;
    }
}
