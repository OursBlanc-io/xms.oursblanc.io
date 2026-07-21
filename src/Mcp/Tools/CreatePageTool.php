<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Blocks\BlockValidator;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\BlockNormalizer;
use OursBlanc\Xms\Support\PageUrlGenerator;

class CreatePageTool extends AbstractXmsTool
{
    protected string $name = 'create_page';

    protected string $description = 'Create a new page. It is always created as a draft — publish it explicitly '.
        'with publish_page once you (or a human) have reviewed the preview URL returned here. '.
        'Call list_block_types first so the `blocks` you send match each type\'s schema.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'locale' => $schema->string()->required(),
            'title' => $schema->string()->required(),
            'slug' => $schema->string()
                ->description('Lowercase, may contain "/" to simulate a path, e.g. "products/smartskin".')
                ->required(),
            'blocks' => $schema->array()->items(
                $schema->object([
                    'uuid' => $schema->string()->description('Omit for a new block; one will be generated.'),
                    'type' => $schema->string()->description('Block type name from list_block_types.')->required(),
                    'data' => $schema->object([])->description('Payload matching the block type\'s schema.'),
                ])
            )->required(),
            'seo' => $schema->object([])->description('title, description, canonical, og_title, og_description, robots, structured_data.'),
            'translation_group_id' => $schema->integer()->description('Attach to an existing translation group instead of creating a new one.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate([
            'locale' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:500',
                'regex:'.Page::SLUG_REGEX,
                Rule::unique('xms_pages', 'slug')->where('locale', $request->get('locale')),
            ],
            'blocks' => 'required|array',
            'blocks.*.type' => 'required|string',
            'seo' => 'array',
            'translation_group_id' => 'nullable|integer',
        ]);

        // Laravel's validate() only returns keys it has explicit rules for, which
        // would silently strip each block's `data` (no rule declared for it,
        // since its shape depends on the block's own type). Use the raw
        // argument for the actual content; $data above only asserted its shape.
        $blocks = BlockNormalizer::normalize($request->get('blocks'));

        try {
            app(BlockValidator::class)->validateBlocks($blocks);
        } catch (ValidationException $e) {
            return $this->blockValidationError($e, $blocks);
        }

        $page = Page::create([
            'translation_group_id' => $data['translation_group_id'] ?? null,
            'locale' => $data['locale'],
            'slug' => $data['slug'],
            'title' => $data['title'],
            'blocks' => $blocks,
            'seo' => $data['seo'] ?? [],
            'status' => 'draft',
        ]);

        return Response::structured([
            'id' => $page->id,
            'status' => $page->status,
            'urls' => [
                'public' => PageUrlGenerator::for($page),
                'preview' => URL::temporarySignedRoute('xms.preview', now()->addMinutes(30), ['page' => $page->id]),
            ],
        ]);
    }
}
