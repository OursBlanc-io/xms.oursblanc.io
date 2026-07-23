<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Form;

class CreateFormTool extends AbstractXmsTool
{
    protected string $name = 'create_form';

    protected string $description = 'Create a form together with its fields. Attach it to a page with the '
        .'`form` block (using the returned id) to render it publicly.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'slug' => $schema->string()->description('Defaults to a slugified version of the name.'),
            'success_message' => $schema->string()->description('Shown after a successful submission.'),
            'submit_label' => $schema->string()->description('Submit button text. Defaults to "Submit".'),
            'notification_emails' => $schema->array()->items($schema->string())
                ->description('Email addresses notified on each submission.'),
            'webhook_enabled' => $schema->boolean(),
            'webhook_url' => $schema->string(),
            'fields' => $schema->array()->items(
                $schema->object([
                    'label' => $schema->string()->required(),
                    'key' => $schema->string()->description('Machine name; defaults to a slugified label.'),
                    'type' => $schema->string()->enum(['text', 'email', 'textarea', 'select', 'checkbox'])->required(),
                    'options' => $schema->array()->items($schema->string())->description('Choices, for type "select".'),
                    'is_required' => $schema->boolean(),
                ])
            )->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('forms:write');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('xms_forms', 'slug')],
            'success_message' => 'nullable|string|max:255',
            'submit_label' => 'nullable|string|max:255',
            'notification_emails' => 'array',
            'notification_emails.*' => 'email',
            'webhook_enabled' => 'boolean',
            'webhook_url' => 'nullable|url',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.key' => 'nullable|string|max:255',
            'fields.*.type' => 'required|in:text,email,textarea,select,checkbox',
            'fields.*.options' => 'array',
            'fields.*.is_required' => 'boolean',
        ]);

        $form = Form::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'success_message' => $data['success_message'] ?? null,
            'submit_label' => $data['submit_label'] ?? null,
            'notification_emails' => $data['notification_emails'] ?? [],
            'webhook_enabled' => $data['webhook_enabled'] ?? false,
            'webhook_url' => $data['webhook_url'] ?? null,
        ]);

        foreach ($data['fields'] as $index => $field) {
            $form->fields()->create([
                'label' => $field['label'],
                'key' => $field['key'] ?? Str::slug($field['label'], '_'),
                'type' => $field['type'],
                'options' => $field['options'] ?? null,
                'is_required' => $field['is_required'] ?? false,
                'sort_order' => $index,
            ]);
        }

        return Response::structured(['id' => $form->id, 'slug' => $form->slug]);
    }
}
