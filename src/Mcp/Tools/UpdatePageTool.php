<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Blocks\BlockValidator;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\BlockNormalizer;

class UpdatePageTool extends AbstractXmsTool
{
    protected string $name = 'update_page';

    protected string $description = 'Replace the given fields of an existing page. Only the fields you pass are '.
        'changed. Existing block uuids are preserved when you return them in `blocks`; blocks without a uuid '.
        'are treated as new. To edit a single block without resending the whole page, use patch_blocks instead.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'title' => $schema->string(),
            'slug' => $schema->string(),
            'blocks' => $schema->array()->items(
                $schema->object([
                    'uuid' => $schema->string(),
                    'type' => $schema->string()->required(),
                    'data' => $schema->object([]),
                ])
            ),
            'seo' => $schema->object([]),
            'category_ids' => $schema->array()->items($schema->integer())
                ->description('Replaces the page\'s categories entirely. Omit to leave categories unchanged.'),
            'list_title' => $schema->string(),
            'list_excerpt' => $schema->string(),
            'meta' => $schema->object([])->description('Replaces the page\'s entire metadata object. Omit to leave it unchanged.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate(['id' => 'required|integer']);
        $page = Page::find($data['id']);

        if (! $page) {
            return Response::error("No page with id [{$data['id']}].");
        }

        $rules = [
            'title' => 'sometimes|string|max:255',
            'seo' => 'sometimes|array',
            'category_ids' => 'sometimes|array',
            'category_ids.*' => 'integer|exists:xms_categories,id',
            'list_title' => 'sometimes|nullable|string|max:255',
            'list_excerpt' => 'sometimes|nullable|string',
            'meta' => 'sometimes|array',
            'meta.*' => 'string',
        ];

        if ($request->get('slug') !== null) {
            $rules['slug'] = [
                ...$this->slugRules(),
                Rule::unique('xms_pages', 'slug')->where('locale', $page->locale)->ignore($page->id),
            ];
        }

        if ($request->get('blocks') !== null) {
            $rules['blocks'] = 'array';
            $rules['blocks.*.type'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $update = array_intersect_key($validated, array_flip(['title', 'slug', 'seo', 'list_title', 'list_excerpt', 'meta']));

        if (isset($validated['category_ids'])) {
            $page->categories()->sync($validated['category_ids']);
        }

        if (isset($validated['blocks'])) {
            // See CreatePageTool: validate() strips each block's `data` since no
            // rule is declared for it, so the raw argument is used instead.
            $blocks = BlockNormalizer::normalize($request->get('blocks'));

            try {
                app(BlockValidator::class)->validateBlocks($blocks);
            } catch (ValidationException $e) {
                return $this->blockValidationError($e, $blocks);
            }

            $update['blocks'] = $blocks;
        }

        $page->update($update);

        return Response::structured([
            'id' => $page->id,
            'updated_fields' => array_keys($update),
        ]);
    }
}
