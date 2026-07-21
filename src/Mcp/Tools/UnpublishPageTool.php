<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Page;

class UnpublishPageTool extends AbstractXmsTool
{
    protected string $name = 'unpublish_page';

    protected string $description = 'Unpublish a page, taking it out of public reach and triggering a CDN cache purge.';

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->required()];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:publish');

        $data = $request->validate(['id' => 'required|integer']);
        $page = Page::find($data['id']);

        if (! $page) {
            return Response::error("No page with id [{$data['id']}].");
        }

        $page->update(['status' => 'draft']);

        return Response::structured(['id' => $page->id, 'status' => $page->status]);
    }
}
