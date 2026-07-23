<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Menu;

class CreateMenuTool extends AbstractXmsTool
{
    protected string $name = 'create_menu';

    protected string $description = 'Create a menu for a (location, locale) pair — e.g. "header"/"fr". '.
        'Each item has a `label` and either `link_type: "page"` with a `page_id`, or `link_type: "url"` with a '.
        'raw `url` (also used for anchors like "#formats"). Items may have one level of `children`, no deeper.';

    protected function itemSchema(JsonSchema $schema, bool $withChildren): array
    {
        $fields = [
            'label' => $schema->string()->required(),
            'link_type' => $schema->string()->enum(['page', 'url'])->description('Defaults to "url".'),
            'url' => $schema->string()->description('Required when link_type is "url".'),
            'page_id' => $schema->integer()->description('Required when link_type is "page".'),
        ];

        if ($withChildren) {
            $fields['children'] = $schema->array()->items($schema->object($this->itemSchema($schema, false)));
        }

        return $fields;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'location' => $schema->string()->description('Free-form key the theme uses to fetch this menu, e.g. "header".')->required(),
            'locale' => $schema->string()->required(),
            'name' => $schema->string()->description('Admin-only label.')->required(),
            'items' => $schema->array()->items($schema->object($this->itemSchema($schema, true)))->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('menus:write');

        $validated = $request->validate([
            'location' => [
                'required', 'string', 'max:255',
                Rule::unique('xms_menus', 'location')->where('locale', $request->get('locale')),
            ],
            'locale' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            ...$this->menuItemRules(),
        ]);

        // validate() only returns keys with an explicit rule; item fields
        // beyond the leaf ones asserted above come from the raw argument.
        $menu = Menu::create([
            'location' => $validated['location'],
            'locale' => $validated['locale'],
            'name' => $validated['name'],
            'items' => $request->get('items'),
        ]);

        return Response::structured(['id' => $menu->id]);
    }
}
