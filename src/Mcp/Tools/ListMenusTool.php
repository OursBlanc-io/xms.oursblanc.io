<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Menu;

class ListMenusTool extends AbstractXmsTool
{
    protected string $name = 'list_menus';

    protected string $description = 'List menus, optionally filtered by location and/or locale. Each menu is '.
        'identified by the (location, locale) pair — a location like "header" or "footer" has one menu per locale.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('Filter by location, e.g. "header".'),
            'locale' => $schema->string()->description('Filter by locale, e.g. "fr".'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('menus:read');

        $query = Menu::query();

        if ($location = $request->get('location')) {
            $query->location($location);
        }

        if ($locale = $request->get('locale')) {
            $query->locale($locale);
        }

        $menus = $query->orderBy('location')->orderBy('locale')
            ->get(['id', 'name', 'location', 'locale', 'updated_at']);

        return Response::structured(['menus' => $menus->toArray()]);
    }
}
