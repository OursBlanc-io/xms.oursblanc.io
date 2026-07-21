<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Support\PageUrlGenerator;

class PublishPageTool extends AbstractXmsTool
{
    protected string $name = 'publish_page';

    protected string $description = 'Publish a draft page. This makes it publicly reachable and triggers a CDN cache purge.';

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

        $page->update(['status' => 'published', 'published_at' => now()]);

        return Response::structured([
            'id' => $page->id,
            'status' => $page->status,
            'url' => PageUrlGenerator::for($page),
        ]);
    }
}
