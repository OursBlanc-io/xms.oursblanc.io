<?php

namespace OursBlanc\Xms\Blocks;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;

class SchemaGenerator
{
    /**
     * @param  array<int, Component>  $fields
     * @param  array<int, string>  $mediaFields  dot-paths, wildcards for repeater items (e.g. "images.*.image")
     * @return array<string, mixed>
     */
    public static function schemaFromFields(array $fields, array $mediaFields = []): array
    {
        $properties = [];
        $required = [];

        foreach ($fields as $field) {
            $name = static::nameOf($field);

            if ($name === null) {
                continue;
            }

            $properties[$name] = static::propertyFor($field, $name, $mediaFields);

            if (static::isRequired($field)) {
                $required[] = $name;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @param  array<int, Component>  $fields
     * @param  array<int, string>  $mediaFields
     * @return array<string, array<int, string>>
     */
    public static function rulesFromFields(array $fields, array $mediaFields = [], string $prefix = ''): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $name = static::nameOf($field);

            if ($name === null) {
                continue;
            }

            $key = $prefix === '' ? $name : "{$prefix}.{$name}";

            $rules[$key] = array_merge(
                [static::isRequired($field) ? 'required' : 'nullable'],
                static::ruleTypeFor($field, $name, $mediaFields),
            );

            if ($field instanceof Repeater) {
                $rules = array_merge(
                    $rules,
                    static::rulesFromFields(
                        $field->getDefaultChildComponents(),
                        static::childMediaFields($mediaFields, $name),
                        "{$key}.*",
                    ),
                );
            }
        }

        return $rules;
    }

    /**
     * @param  array<int, string>  $mediaFields
     * @return array<string, mixed>
     */
    protected static function propertyFor(Component $field, string $name, array $mediaFields): array
    {
        if (in_array($name, $mediaFields, true)) {
            return ['type' => 'integer', 'x-media' => true];
        }

        if ($field instanceof Builder) {
            return static::builderProperty();
        }

        if ($field instanceof Repeater) {
            return [
                'type' => 'array',
                'items' => static::schemaFromFields(
                    $field->getDefaultChildComponents(),
                    static::childMediaFields($mediaFields, $name),
                ),
            ];
        }

        return static::propertyFromComponent($field);
    }

    /**
     * A Builder field holds a heterogeneous list of *other blocks* (see
     * Block::nestedBlockFields()) — each item's own `data` shape depends on
     * its `type`, so unlike a Repeater there's no single fixed sub-schema to
     * report. This generic uuid/type/data envelope is the honest schema;
     * callers look up each type's own `data` shape via list_block_types.
     *
     * @return array<string, mixed>
     */
    protected static function builderProperty(): array
    {
        return [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'type' => ['type' => 'string'],
                    'data' => ['type' => 'object'],
                    'uuid' => ['type' => 'string'],
                ],
                'required' => ['type', 'data'],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $mediaFields
     * @return array<int, string>
     */
    protected static function ruleTypeFor(Component $field, string $name, array $mediaFields): array
    {
        if (in_array($name, $mediaFields, true)) {
            return ['integer'];
        }

        return match (true) {
            $field instanceof Toggle => ['boolean'],
            $field instanceof Repeater, $field instanceof Builder => ['array'],
            default => ['string'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected static function propertyFromComponent(Component $field): array
    {
        return match (true) {
            $field instanceof MarkdownEditor => ['type' => 'string', 'format' => 'markdown'],
            $field instanceof Toggle => ['type' => 'boolean'],
            $field instanceof Select => static::selectProperty($field),
            $field instanceof Textarea => ['type' => 'string'],
            default => ['type' => 'string'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected static function selectProperty(Select $field): array
    {
        $schema = ['type' => 'string'];

        $options = $field->getOptions();

        if ($options !== []) {
            $schema['enum'] = array_keys($options);
        }

        return $schema;
    }

    /**
     * @param  array<int, string>  $mediaFields
     * @return array<int, string>
     */
    protected static function childMediaFields(array $mediaFields, string $repeaterName): array
    {
        $prefix = "{$repeaterName}.*.";
        $child = [];

        foreach ($mediaFields as $path) {
            if (str_starts_with($path, $prefix)) {
                $child[] = substr($path, strlen($prefix));
            }
        }

        return $child;
    }

    protected static function nameOf(mixed $field): ?string
    {
        // Entry components (e.g. a Placeholder used for an admin-only preview)
        // are display-only: they're never dehydrated into real form/block
        // data, so they must not leak into the schema advertised to the AI.
        if ($field instanceof Entry) {
            return null;
        }

        if (! $field instanceof Component || ! method_exists($field, 'getName')) {
            return null;
        }

        return $field->getName();
    }

    protected static function isRequired(Component $field): bool
    {
        return method_exists($field, 'isRequired') && $field->isRequired();
    }
}
