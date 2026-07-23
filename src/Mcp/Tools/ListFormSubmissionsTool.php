<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Form;

class ListFormSubmissionsTool extends AbstractXmsTool
{
    protected string $name = 'list_form_submissions';

    protected string $description = 'List submissions for a given form, most recent first.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'form_id' => $schema->integer()->required(),
            'limit' => $schema->integer()->description('Defaults to 50.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('forms:read');

        $data = $request->validate([
            'form_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $form = Form::find($data['form_id']);

        if (! $form) {
            return Response::error("No form with id [{$data['form_id']}].");
        }

        $submissions = $form->submissions()->limit($data['limit'] ?? 50)->get(['id', 'data', 'ip_address', 'created_at']);

        return Response::structured(['submissions' => $submissions->toArray()]);
    }
}
