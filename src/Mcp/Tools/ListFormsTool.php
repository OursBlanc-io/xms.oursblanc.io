<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Form;

class ListFormsTool extends AbstractXmsTool
{
    protected string $name = 'list_forms';

    protected string $description = 'List every form, with its field and submission counts.';

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('forms:read');

        $forms = Form::query()
            ->withCount(['fields', 'submissions'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'webhook_enabled']);

        return Response::structured(['forms' => $forms->toArray()]);
    }
}
