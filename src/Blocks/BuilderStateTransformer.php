<?php

namespace OursBlanc\Xms\Blocks;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Filament's Builder component stores each item as a plain ['type', 'data'] pair
 * and does not preserve custom identifiers across dehydration. XMS needs a stable
 * per-block uuid (used as the media collection key and as the MCP update target),
 * so it is carried as a hidden field inside each block's own data and moved in/out
 * of the top-level `uuid` key at the form/storage boundary.
 */
class BuilderStateTransformer
{
    /**
     * @param  array<int, array{uuid: string, type: string, data: array<string, mixed>}>  $storedBlocks
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public static function toBuilderState(array $storedBlocks): array
    {
        return array_map(
            fn (array $block) => [
                'type' => $block['type'],
                'data' => array_merge($block['data'] ?? [], ['uuid' => $block['uuid']]),
            ],
            $storedBlocks,
        );
    }

    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $builderState
     * @return array<int, array{uuid: string, type: string, data: array<string, mixed>}>
     */
    public static function toStoredState(array $builderState): array
    {
        return array_values(array_map(
            fn (array $block) => [
                'uuid' => $block['data']['uuid'] ?? (string) Str::uuid(),
                'type' => $block['type'],
                'data' => Arr::except($block['data'] ?? [], 'uuid'),
            ],
            $builderState,
        ));
    }
}
