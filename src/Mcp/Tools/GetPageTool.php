<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\URL;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\PageUrlGenerator;

class GetPageTool extends AbstractXmsTool
{
    protected string $name = 'get_page';

    protected string $description = 'Get a page in full: its blocks (with uuids), SEO metadata, public and preview URLs, and its sibling locales.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Page id.')->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:read');

        $data = $request->validate(['id' => 'required|integer']);

        $page = Page::find($data['id']);

        if (! $page) {
            return Response::error("No page with id [{$data['id']}].");
        }

        return Response::structured([
            'id' => $page->id,
            'translation_group_id' => $page->translation_group_id,
            'locale' => $page->locale,
            'slug' => $page->slug,
            'title' => $page->title,
            'status' => $page->status,
            'template' => $page->template,
            'blocks' => $page->blocks,
            'seo' => $page->seo,
            'urls' => [
                'public' => PageUrlGenerator::for($page),
                'preview' => URL::temporarySignedRoute('xms.preview', now()->addMinutes(30), ['page' => $page->id]),
            ],
            'sibling_locales' => $page->siblingLocales()
                ->get(['id', 'locale', 'slug', 'status'])
                ->toArray(),
        ]);
    }
}
