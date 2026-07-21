<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Blocks\BlockRegistry;

class ListBlockTypesTool extends AbstractXmsTool
{
    protected string $name = 'list_block_types';

    protected string $description = 'List every available block type with its JSON Schema and media fields. '
        .'Call this first, before composing any page, to discover which block types exist and exactly what '
        .'data each one expects — no external documentation is needed.';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request, BlockRegistry $registry): Response|ResponseFactory
    {
        $this->authorize('pages:read');

        return Response::structured(['block_types' => array_values($registry->schemas())]);
    }
}
