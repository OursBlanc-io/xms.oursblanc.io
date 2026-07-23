<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Category;

class CreateCategoryTool extends AbstractXmsTool
{
    protected string $name = 'create_category';

    protected string $description = 'Create a category that pages can be attached to (e.g. for a blog index via '
        .'the page_list block). Slug is derived from the name when omitted.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'slug' => $schema->string()->description('Defaults to a slugified version of the name.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('xms_categories', 'slug')],
        ]);

        $category = Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
        ]);

        return Response::structured(['id' => $category->id, 'slug' => $category->slug]);
    }
}
