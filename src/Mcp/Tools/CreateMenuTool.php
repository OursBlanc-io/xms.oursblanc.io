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
        'Each item has a `label` and one of: `link_type: "page"` with a `page_id`, `link_type: "url"` with a '.
        'raw `url` (also used for anchors like "#formats"), or `link_type: "language_switch"` with a '.
        '`target_locale` (links to the current page\'s translation in that locale, or its homepage if none — '.
        'use this instead of hardcoding a locale URL, since the target adapts per page). `target` ("_self", the '.
        'default, or "_blank") controls whether page/url links open in a new tab; not used for language_switch. '.
        'Top-level items may set `display` ("link", the default, "button_primary", or "button_secondary") to '.
        'render as a button instead of a plain link — children (dropdown entries) are always plain links. Items '.
        'may have one level of `children`, no deeper.';

    protected function itemSchema(JsonSchema $schema, bool $withChildren): array
    {
        $fields = [
            'label' => $schema->string()->required(),
            'link_type' => $schema->string()->enum(['page', 'url', 'language_switch'])->description('Defaults to "url".'),
            'url' => $schema->string()->description('Required when link_type is "url".'),
            'page_id' => $schema->integer()->description('Required when link_type is "page".'),
            'target_locale' => $schema->string()->description('Required when link_type is "language_switch", e.g. "en".'),
            'target' => $schema->string()->enum(['_self', '_blank'])->description('Defaults to "_self". Not used for language_switch.'),
        ];

        if ($withChildren) {
            $fields['display'] = $schema->string()->enum(['link', 'button_primary', 'button_secondary'])
                ->description('Defaults to "link". Only meaningful on top-level items.');
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
