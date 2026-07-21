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
use OursBlanc\Xms\Support\PageUrlGenerator;

class TranslatePageTool extends AbstractXmsTool
{
    protected string $name = 'translate_page';

    protected string $description = 'Create a translated sibling of an existing page in its translation group. '.
        'You provide the already-translated blocks and SEO; this tool enforces that the translated blocks have '.
        'the exact same block types in the exact same order as the source page (rejecting the call with an '.
        'explicit error otherwise) and preserves each block\'s uuid so its media stays attached.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Source page id.')->required(),
            'target_locale' => $schema->string()->required(),
            'title' => $schema->string()->description('Translated page title.')->required(),
            'slug' => $schema->string()->required(),
            'blocks_translated' => $schema->array()->items(
                $schema->object([
                    'type' => $schema->string()->required(),
                    'data' => $schema->object([])->required(),
                ])
            )->description('Same length, types, and order as the source page\'s blocks — translated data only.')->required(),
            'seo_translated' => $schema->object([]),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate([
            'id' => 'required|integer',
            'target_locale' => 'required|string|max:10',
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:500', 'regex:'.Page::SLUG_REGEX],
            'blocks_translated' => 'required|array',
            'blocks_translated.*.type' => 'required|string',
            'seo_translated' => 'array',
        ]);

        $source = Page::find($data['id']);

        if (! $source) {
            return Response::error("No page with id [{$data['id']}].");
        }

        $exists = Page::query()
            ->where('translation_group_id', $source->translation_group_id)
            ->where('locale', $data['target_locale'])
            ->exists();

        if ($exists) {
            return Response::error("The translation group already has a page in locale [{$data['target_locale']}].");
        }

        // See CreatePageTool: validate() strips each block's `data` since no
        // rule is declared for it. Use the raw argument for the actual content.
        $blocksTranslated = $request->get('blocks_translated');

        $sourceTypes = collect($source->blocks)->pluck('type')->all();
        $translatedTypes = collect($blocksTranslated)->pluck('type')->all();

        if ($sourceTypes !== $translatedTypes) {
            return Response::error(
                'blocks_translated must have the same block types in the same order as the source page. '.
                'Source: ['.implode(', ', $sourceTypes).']. Given: ['.implode(', ', $translatedTypes).'].',
            );
        }

        // Preserve each source block's uuid so its already-attached media stays associated.
        $blocks = collect($blocksTranslated)
            ->values()
            ->map(fn (array $block, int $index) => [
                'uuid' => $source->blocks[$index]['uuid'],
                'type' => $block['type'],
                'data' => $block['data'] ?? [],
            ])
            ->all();

        try {
            app(BlockValidator::class)->validateBlocks($blocks);
        } catch (ValidationException $e) {
            return $this->blockValidationError($e, $blocks);
        }

        $validator = validator(
            ['slug' => $data['slug']],
            ['slug' => [Rule::unique('xms_pages', 'slug')->where('locale', $data['target_locale'])]],
        );

        if ($validator->fails()) {
            return Response::error("Slug [{$data['slug']}] is already used in locale [{$data['target_locale']}].");
        }

        $translated = Page::create([
            'translation_group_id' => $source->translation_group_id,
            'locale' => $data['target_locale'],
            'slug' => $data['slug'],
            'title' => $data['title'],
            'blocks' => $blocks,
            'seo' => $data['seo_translated'] ?? [],
            'template' => $source->template,
            'status' => 'draft',
        ]);

        return Response::structured([
            'id' => $translated->id,
            'status' => $translated->status,
            'urls' => [
                'public' => PageUrlGenerator::for($translated),
                'preview' => URL::temporarySignedRoute('xms.preview', now()->addMinutes(30), ['page' => $translated->id]),
            ],
        ]);
    }
}
