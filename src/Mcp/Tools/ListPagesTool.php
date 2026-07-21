<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Page;

class ListPagesTool extends AbstractXmsTool
{
    protected string $name = 'list_pages';

    protected string $description = 'List pages, optionally filtered by locale, status, or a search term matched against title/slug.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'locale' => $schema->string()->description('Filter by locale, e.g. "fr".'),
            'status' => $schema->string()->enum(['draft', 'published'])->description('Filter by status.'),
            'search' => $schema->string()->description('Case-insensitive search within title or slug.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('pages:read');

        $query = Page::query();

        if ($locale = $request->get('locale')) {
            $query->locale($locale);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        $pages = $query->orderByDesc('updated_at')
            ->get(['id', 'title', 'slug', 'locale', 'status', 'updated_at', 'translation_group_id']);

        return Response::structured(['pages' => $pages->toArray()]);
    }
}
