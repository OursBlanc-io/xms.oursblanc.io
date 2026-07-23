<?php

namespace OursBlanc\Xms\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use OursBlanc\Xms\Models\Form;

class UpdateFormTool extends AbstractXmsTool
{
    protected string $name = 'update_form';

    protected string $description = 'Replace the given fields of an existing form. Only the fields you pass are '
        .'changed. Passing `fields` replaces the form\'s entire field list.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->required(),
            'name' => $schema->string(),
            'slug' => $schema->string(),
            'success_message' => $schema->string(),
            'submit_label' => $schema->string()->description('Submit button text. Defaults to "Submit".'),
            'notification_emails' => $schema->array()->items($schema->string()),
            'webhook_enabled' => $schema->boolean(),
            'webhook_url' => $schema->string(),
            'fields' => $schema->array()->items(
                $schema->object([
                    'label' => $schema->string()->required(),
                    'key' => $schema->string(),
                    'type' => $schema->string()->enum(['text', 'email', 'textarea', 'select', 'checkbox'])->required(),
                    'options' => $schema->array()->items($schema->string()),
                    'is_required' => $schema->boolean(),
                ])
            ),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $this->authorize('forms:write');

        $data = $request->validate(['id' => 'required|integer']);

        $form = Form::find($data['id']);

        if (! $form) {
            return Response::error("No form with id [{$data['id']}].");
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('xms_forms', 'slug')->ignore($form->id)],
            'success_message' => 'sometimes|nullable|string|max:255',
            'submit_label' => 'sometimes|nullable|string|max:255',
            'notification_emails' => 'sometimes|array',
            'notification_emails.*' => 'email',
            'webhook_enabled' => 'sometimes|boolean',
            'webhook_url' => 'sometimes|nullable|url',
            'fields' => 'sometimes|array',
            'fields.*.label' => 'required_with:fields|string|max:255',
            'fields.*.key' => 'nullable|string|max:255',
            'fields.*.type' => 'required_with:fields|in:text,email,textarea,select,checkbox',
            'fields.*.options' => 'array',
            'fields.*.is_required' => 'boolean',
        ]);

        $update = array_intersect_key($validated, array_flip([
            'name', 'slug', 'success_message', 'submit_label', 'notification_emails', 'webhook_enabled', 'webhook_url',
        ]));

        $form->update($update);

        if (isset($validated['fields'])) {
            $form->fields()->delete();

            foreach ($validated['fields'] as $index => $field) {
                $form->fields()->create([
                    'label' => $field['label'],
                    'key' => $field['key'] ?? Str::slug($field['label'], '_'),
                    'type' => $field['type'],
                    'options' => $field['options'] ?? null,
                    'is_required' => $field['is_required'] ?? false,
                    'sort_order' => $index,
                ]);
            }
        }

        return Response::structured([
            'id' => $form->id,
            'updated_fields' => array_keys($update),
        ]);
    }
}
