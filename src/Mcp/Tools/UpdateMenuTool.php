<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Menu;

class UpdateMenuTool extends AbstractXmsTool
{
    protected string $name = 'update_menu';

    protected string $description = 'Replace the given fields of an existing menu. Only the fields you pass are '.
        'changed. Passing `items` replaces the entire items list (including all children) — there is no partial '.
        'patch for individual items, so include every item you want to keep, with all of its fields (label, '.
        'link_type, url/page_id/target_locale, target, display) — omitting a field on an existing item clears it '.
        'rather than preserving its previous value.';

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
            'id' => $schema->integer()->required(),
            'name' => $schema->string(),
            'items' => $schema->array()->items($schema->object($this->itemSchema($schema, true))),
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

        $rules = ['name' => 'sometimes|string|max:255'];

        if ($request->get('items') !== null) {
            $rules = [...$rules, ...$this->menuItemRules()];
        }

        $validated = $request->validate($rules);

        $update = array_intersect_key($validated, array_flip(['name']));

        if ($request->get('items') !== null) {
            // validate() only returns keys with an explicit rule; the raw
            // argument carries the actual item content (see menuItemRules).
            $update['items'] = $request->get('items');
        }

        $menu->update($update);

        return Response::structured([
            'id' => $menu->id,
            'updated_fields' => array_keys($update),
        ]);
    }
}
