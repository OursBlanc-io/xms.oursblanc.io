<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Category;

class ListCategoriesTool extends AbstractXmsTool
{
    protected string $name = 'list_categories';

    protected string $description = 'List every category available to attach to pages, e.g. for a blog index. '
        .'Call this before create_page/update_page if you plan to set category_ids.';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:read');

        return Response::structured([
            'categories' => Category::query()->orderBy('name')->get(['id', 'name', 'slug'])->toArray(),
        ]);
    }
}
