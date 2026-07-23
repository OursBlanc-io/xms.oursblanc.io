<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Category;

class UpdateCategoryTool extends AbstractXmsTool
{
    protected string $name = 'update_category';

    protected string $description = 'Rename a category and/or change its slug. Only the fields you pass are changed.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'name' => $schema->string(),
            'slug' => $schema->string(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate(['id' => 'required|integer']);
        $category = Category::find($data['id']);

        if (! $category) {
            return Response::error("No category with id [{$data['id']}].");
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('xms_categories', 'slug')->ignore($category->id)],
        ]);

        $category->update($validated);

        return Response::structured([
            'id' => $category->id,
            'updated_fields' => array_keys($validated),
        ]);
    }
}
