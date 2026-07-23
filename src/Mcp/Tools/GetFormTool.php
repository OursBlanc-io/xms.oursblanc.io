<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Form;

class GetFormTool extends AbstractXmsTool
{
    protected string $name = 'get_form';

    protected string $description = 'Get a form in full: its fields, notification settings, and webhook configuration.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('forms:read');

        $data = $request->validate(['id' => 'required|integer']);

        $form = Form::with('fields')->find($data['id']);

        if (! $form) {
            return Response::error("No form with id [{$data['id']}].");
        }

        return Response::structured([
            'id' => $form->id,
            'name' => $form->name,
            'slug' => $form->slug,
            'success_message' => $form->success_message,
            'submit_label' => $form->submit_label,
            'notification_emails' => $form->notification_emails,
            'webhook_enabled' => $form->webhook_enabled,
            'webhook_url' => $form->webhook_url,
            'fields' => $form->fields->map(fn ($field) => [
                'id' => $field->id,
                'label' => $field->label,
                'key' => $field->key,
                'type' => $field->type,
                'options' => $field->options,
                'is_required' => $field->is_required,
            ])->all(),
        ]);
    }
}
