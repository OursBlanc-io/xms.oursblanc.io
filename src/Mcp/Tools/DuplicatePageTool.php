<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\URL;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\PageDuplicator;
use OursBlanc\Xms\Support\PageUrlGenerator;

class DuplicatePageTool extends AbstractXmsTool
{
    protected string $name = 'duplicate_page';

    protected string $description = 'Create an independent draft copy of a page — a new page (own slug, own '.
        'media) that can be edited freely without affecting the original. For a translation instead, use '.
        'translate_page.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Page id to duplicate.')->required(),
        ];
    }

    public function handle(Request $request, PageDuplicator $duplicator): Response|ResponseFactory
    {
        $this->authorize('pages:write');

        $data = $request->validate(['id' => 'required|integer']);

        $page = Page::find($data['id']);

        if (! $page) {
            return Response::error("No page with id [{$data['id']}].");
        }

        $duplicate = $duplicator->duplicate($page);

        return Response::structured([
            'id' => $duplicate->id,
            'slug' => $duplicate->slug,
            'urls' => [
                'public' => PageUrlGenerator::for($duplicate),
                'preview' => URL::temporarySignedRoute('xms.preview', now()->addMinutes(30), ['page' => $duplicate->id]),
            ],
        ]);
    }
}
