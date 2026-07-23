<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Category;

class DeleteCategoryTool extends AbstractXmsTool
{
    protected string $name = 'delete_category';

    protected string $description = 'Permanently delete a category by id. Pages attached to it simply lose the '.
        'attachment — they are not deleted.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
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

        $category->delete();

        return Response::structured(['id' => $data['id'], 'deleted' => true]);
    }
}
