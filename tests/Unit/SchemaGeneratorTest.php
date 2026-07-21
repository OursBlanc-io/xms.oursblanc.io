<?php

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use OursBlanc\Xms\Blocks\SchemaGenerator;

it('maps a TextInput to a string property', function () {
    $schema = SchemaGenerator::schemaFromFields([
        TextInput::make('title'),
    ]);

    expect($schema['properties']['title'])->toBe(['type' => 'string']);
});

it('maps a Textarea to a string property', function () {
    $schema = SchemaGenerator::schemaFromFields([
        Textarea::make('summary'),
    ]);

    expect($schema['properties']['summary'])->toBe(['type' => 'string']);
});

it('maps a MarkdownEditor to a string property with a markdown format', function () {
    $schema = SchemaGenerator::schemaFromFields([
        MarkdownEditor::make('content'),
    ]);

    expect($schema['properties']['content'])->toBe(['type' => 'string', 'format' => 'markdown']);
});

it('maps a Toggle to a boolean property', function () {
    $schema = SchemaGenerator::schemaFromFields([
        Toggle::make('is_featured'),
    ]);

    expect($schema['properties']['is_featured'])->toBe(['type' => 'boolean']);
});

it('maps a Select to a string property with an enum derived from its options', function () {
    $schema = SchemaGenerator::schemaFromFields([
        Select::make('alignment')->options(['left' => 'Left', 'right' => 'Right']),
    ]);

    expect($schema['properties']['alignment'])->toBe([
        'type' => 'string',
        'enum' => ['left', 'right'],
    ]);
});

it('maps a Select without options to a plain string property', function () {
    $schema = SchemaGenerator::schemaFromFields([
        Select::make('alignment'),
    ]);

    expect($schema['properties']['alignment'])->toBe(['type' => 'string']);
});

it('maps a media field to an integer property annotated with x-media', function () {
    $schema = SchemaGenerator::schemaFromFields([
        TextInput::make('image')->numeric(),
    ], mediaFields: ['image']);

    expect($schema['properties']['image'])->toBe(['type' => 'integer', 'x-media' => true]);
});

it('maps a Repeater to an array property with a nested item schema', function () {
    $schema = SchemaGenerator::schemaFromFields([
        Repeater::make('columns')->schema([
            TextInput::make('title'),
            MarkdownEditor::make('content'),
        ]),
    ]);

    expect($schema['properties']['columns'])->toBe([
        'type' => 'array',
        'items' => [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'content' => ['type' => 'string', 'format' => 'markdown'],
            ],
        ],
    ]);
});

it('marks nested media fields inside a Repeater via dot-star paths', function () {
    $schema = SchemaGenerator::schemaFromFields([
        Repeater::make('images')->schema([
            TextInput::make('image')->numeric(),
            TextInput::make('alt'),
        ]),
    ], mediaFields: ['images.*.image']);

    expect($schema['properties']['images']['items']['properties']['image'])
        ->toBe(['type' => 'integer', 'x-media' => true])
        ->and($schema['properties']['images']['items']['properties']['alt'])
        ->toBe(['type' => 'string']);
});

it('lists required fields at the top level of the schema', function () {
    $schema = SchemaGenerator::schemaFromFields([
        TextInput::make('title')->required(),
        TextInput::make('subtitle'),
    ]);

    expect($schema['required'])->toBe(['title']);
});

it('omits the required key entirely when no field is required', function () {
    $schema = SchemaGenerator::schemaFromFields([
        TextInput::make('subtitle'),
    ]);

    expect($schema)->not->toHaveKey('required');
});

it('derives Laravel validation rules from field types and requiredness', function () {
    $rules = SchemaGenerator::rulesFromFields([
        TextInput::make('title')->required(),
        Toggle::make('is_featured'),
        TextInput::make('image')->numeric(),
    ], mediaFields: ['image']);

    expect($rules)->toBe([
        'title' => ['required', 'string'],
        'is_featured' => ['nullable', 'boolean'],
        'image' => ['nullable', 'integer'],
    ]);
});

it('derives nested rules for Repeater items using dot-star notation', function () {
    $rules = SchemaGenerator::rulesFromFields([
        Repeater::make('columns')->schema([
            TextInput::make('title')->required(),
            TextInput::make('image')->numeric(),
        ]),
    ], mediaFields: ['columns.*.image']);

    expect($rules)->toBe([
        'columns' => ['nullable', 'array'],
        'columns.*.title' => ['required', 'string'],
        'columns.*.image' => ['nullable', 'integer'],
    ]);
});
