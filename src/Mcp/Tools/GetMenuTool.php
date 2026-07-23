<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Menu;

class GetMenuTool extends AbstractXmsTool
{
    protected string $name = 'get_menu';

    protected string $description = 'Get a menu by id, or by (location, locale). Returns both the raw `items` '.
        '(as stored, editable via update_menu) and `resolved_items` (with `resolved_url` computed for internal '.
        'page links, ready to render).';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Menu id. Alternative to (location, locale).'),
            'location' => $schema->string()->description('Used with locale when id is omitted.'),
            'locale' => $schema->string()->description('Used with location when id is omitted.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('menus:read');

        $data = $request->get('id')
            ? ['id' => $request->get('id')]
            : ['location' => $request->get('location'), 'locale' => $request->get('locale')];

        $menu = isset($data['id'])
            ? Menu::find($data['id'])
            : Menu::forLocation((string) $data['location'], (string) $data['locale']);

        if (! $menu) {
            return Response::error('No matching menu.');
        }

        return Response::structured([
            'id' => $menu->id,
            'name' => $menu->name,
            'location' => $menu->location,
            'locale' => $menu->locale,
            'items' => $menu->items,
            'resolved_items' => $menu->resolvedItems(),
        ]);
    }
}
