<?php

namespace OursBlanc\Xms\Support;

use Illuminate\Support\Collection;

class RevisionDiffer
{
    /**
     * Compare two `blocks` arrays and summarize which blocks (by uuid) were
     * added, removed, or modified.
     *
     * @param  array<int, array<string, mixed>>  $before
     * @param  array<int, array<string, mixed>>  $after
     * @return array{added: array<int, string>, removed: array<int, string>, modified: array<int, string>}
     */
    public static function diff(array $before, array $after): array
    {
        $beforeByUuid = static::keyByUuid($before);
        $afterByUuid = static::keyByUuid($after);

        $added = $afterByUuid->keys()->diff($beforeByUuid->keys())->values()->all();
        $removed = $beforeByUuid->keys()->diff($afterByUuid->keys())->values()->all();

        $modified = $beforeByUuid->keys()
            ->intersect($afterByUuid->keys())
            ->filter(fn (string $uuid) => $beforeByUuid[$uuid] !== $afterByUuid[$uuid])
            ->values()
            ->all();

        return compact('added', 'removed', 'modified');
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return Collection<string, array<string, mixed>>
     */
    protected static function keyByUuid(array $blocks): Collection
    {
        return collect($blocks)->keyBy(fn (array $block) => $block['uuid'] ?? '');
    }
}
