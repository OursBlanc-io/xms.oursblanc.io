<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Category;

class GetCategoryTool extends AbstractXmsTool
{
    protected string $name = 'get_category';

    protected string $description = 'Get a category by id or slug, including the ids of every page attached to it.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Category id. Alternative to slug.'),
            'slug' => $schema->string()->description('Alternative to id.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:read');

        $category = $request->get('id')
            ? Category::find($request->get('id'))
            : Category::where('slug', $request->get('slug'))->first();

        if (! $category) {
            return Response::error('No matching category.');
        }

        return Response::structured([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'page_ids' => $category->pages()->pluck('xms_pages.id')->all(),
        ]);
    }
}
