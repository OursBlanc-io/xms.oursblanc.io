<?php

use OursBlanc\Xms\Mcp\Tools\CreateFormTool;
use OursBlanc\Xms\Mcp\Tools\GetFormTool;
use OursBlanc\Xms\Mcp\Tools\ListFormsTool;
use OursBlanc\Xms\Mcp\Tools\ListFormSubmissionsTool;
use OursBlanc\Xms\Mcp\Tools\UpdateFormTool;
use OursBlanc\Xms\Mcp\XmsMcpServer;
use OursBlanc\Xms\Models\Form;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Tests\Feature\Mcp\McpToolTestCase;

uses(McpToolTestCase::class);

afterEach(fn () => Page::$authorResolver = null);

function actingWithFormsAbilities(): void
{
    test()->actingAsApiToken(['pages:read', 'pages:write', 'forms:read', 'forms:write']);
}

it('create_form creates the form and its fields in order', function () {
    actingWithFormsAbilities();

    XmsMcpServer::tool(CreateFormTool::class, [
        'name' => 'Contact',
        'fields' => [
            ['label' => 'Name', 'type' => 'text', 'is_required' => true],
            ['label' => 'Email', 'type' => 'email', 'is_required' => true],
        ],
    ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->where('slug', 'contact')->etc());

    $form = Form::sole();

    expect($form->fields)->toHaveCount(2)
        ->and($form->fields->first()->key)->toBe('name')
        ->and($form->fields->last()->key)->toBe('email');
});

it('list_forms returns field and submission counts', function () {
    actingWithFormsAbilities();

    $form = Form::create(['name' => 'Contact', 'slug' => 'contact']);
    $form->fields()->create(['label' => 'Name', 'key' => 'name', 'type' => 'text', 'sort_order' => 0]);

    XmsMcpServer::tool(ListFormsTool::class)
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('forms', 1)
            ->where('forms.0.fields_count', 1)
            ->where('forms.0.submissions_count', 0)
            ->etc());
});

it('get_form returns the full field list', function () {
    actingWithFormsAbilities();

    $form = Form::create(['name' => 'Contact', 'slug' => 'contact']);
    $form->fields()->create(['label' => 'Name', 'key' => 'name', 'type' => 'text', 'is_required' => true, 'sort_order' => 0]);

    XmsMcpServer::tool(GetFormTool::class, ['id' => $form->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('fields', 1)
            ->where('fields.0.key', 'name')
            ->etc());
});

it('update_form replaces the field list when fields is passed', function () {
    actingWithFormsAbilities();

    $form = Form::create(['name' => 'Contact', 'slug' => 'contact']);
    $form->fields()->create(['label' => 'Old', 'key' => 'old', 'type' => 'text', 'sort_order' => 0]);

    XmsMcpServer::tool(UpdateFormTool::class, [
        'id' => $form->id,
        'fields' => [['label' => 'New', 'type' => 'text']],
    ])->assertOk();

    $form->refresh();

    expect($form->fields)->toHaveCount(1)
        ->and($form->fields->first()->key)->toBe('new');
});

it('list_form_submissions returns submissions most recent first', function () {
    actingWithFormsAbilities();

    $form = Form::create(['name' => 'Contact', 'slug' => 'contact']);
    $form->submissions()->create(['data' => ['name' => 'Alice']])
        ->forceFill(['created_at' => now()->subMinute()])->save();
    $form->submissions()->create(['data' => ['name' => 'Bob']]);

    XmsMcpServer::tool(ListFormSubmissionsTool::class, ['form_id' => $form->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('submissions', 2)
            ->where('submissions.0.data.name', 'Bob')
            ->etc());
});
