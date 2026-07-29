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
 *
 * A block can itself hold nested blocks (see Block::nestedBlockFields(), e.g.
 * Tabbed Showcase's per-tab content) — both directions recurse into every
 * declared nested-blocks path so those get the same uuid hand-off.
 */
class BuilderStateTransformer
{
    /**
     * @param  array<int, array{uuid: string, type: string, data: array<string, mixed>}>  $storedBlocks
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public static function toBuilderState(array $storedBlocks, ?BlockRegistry $registry = null): array
    {
        $registry ??= app(BlockRegistry::class);

        return array_map(
            fn (array $block) => [
                'type' => $block['type'],
                'data' => static::nestBuilderState(
                    array_merge($block['data'] ?? [], ['uuid' => $block['uuid']]),
                    $registry->find($block['type']),
                    $registry,
                ),
            ],
            $storedBlocks,
        );
    }

    /**
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $builderState
     * @return array<int, array{uuid: string, type: string, data: array<string, mixed>}>
     */
    public static function toStoredState(array $builderState, ?BlockRegistry $registry = null): array
    {
        $registry ??= app(BlockRegistry::class);

        return array_values(array_map(
            fn (array $block) => [
                'uuid' => $block['data']['uuid'] ?? (string) Str::uuid(),
                'type' => $block['type'],
                'data' => static::flattenBuilderState(
                    Arr::except($block['data'] ?? [], 'uuid'),
                    $registry->find($block['type']),
                    $registry,
                ),
            ],
            $builderState,
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function nestBuilderState(array $data, ?string $blockClass, BlockRegistry $registry): array
    {
        foreach ($blockClass ? $blockClass::nestedBlockFields() : [] as $path) {
            $data = static::mapNestedPath($data, $path, fn (array $nested) => static::toBuilderState($nested, $registry));
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function flattenBuilderState(array $data, ?string $blockClass, BlockRegistry $registry): array
    {
        foreach ($blockClass ? $blockClass::nestedBlockFields() : [] as $path) {
            $data = static::mapNestedPath($data, $path, fn (array $nested) => static::toStoredState($nested, $registry));
        }

        return $data;
    }

    /**
     * Applies $callback to the array found at a `key` or `key.*.subfield`
     * path inside $data (same `.*.` repeater-wildcard convention as
     * Block::mediaFields()), replacing it with the callback's return value.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function mapNestedPath(array $data, string $path, \Closure $callback): array
    {
        if (! str_contains($path, '.*.')) {
            if (isset($data[$path]) && is_array($data[$path])) {
                $data[$path] = $callback($data[$path]);
            }

            return $data;
        }

        [$repeaterKey, $subField] = explode('.*.', $path, 2);

        if (isset($data[$repeaterKey]) && is_array($data[$repeaterKey])) {
            foreach ($data[$repeaterKey] as &$item) {
                if (is_array($item) && isset($item[$subField]) && is_array($item[$subField])) {
                    $item[$subField] = $callback($item[$subField]);
                }
            }
            unset($item);
        }

        return $data;
    }
}
