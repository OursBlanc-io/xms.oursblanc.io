<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Menu;

class DeleteMenuTool extends AbstractXmsTool
{
    protected string $name = 'delete_menu';

    protected string $description = 'Permanently delete a menu by id.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('menus:write');

        $data = $request->validate(['id' => 'required|integer']);
        $menu = Menu::find($data['id']);

        if (! $menu) {
            return Response::error("No menu with id [{$data['id']}].");
        }

        $menu->delete();

        return Response::structured(['id' => $data['id'], 'deleted' => true]);
    }
}
