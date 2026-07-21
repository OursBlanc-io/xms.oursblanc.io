<?php

namespace OursBlanc\Xms\Support;

use Illuminate\Support\Str;

/**
 * Ensures every block in an MCP-supplied `blocks` array has a stable uuid
 * and a `data` array, generating both where the caller omitted them (e.g.
 * a newly inserted block).
 */
class BlockNormalizer
{
    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array{uuid: string, type: string, data: array<string, mixed>}>
     */
    public static function normalize(array $blocks): array
    {
        return array_values(array_map(
            fn (array $block) => [
                'uuid' => $block['uuid'] ?? (string) Str::uuid(),
                'type' => $block['type'],
                'data' => $block['data'] ?? [],
            ],
            $blocks,
        ));
    }
}
