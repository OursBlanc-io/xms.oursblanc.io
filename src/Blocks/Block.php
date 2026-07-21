<?php

namespace OursBlanc\Xms\Blocks;

use Filament\Forms\Components\Component;

abstract class Block
{
    abstract public static function name(): string;

    abstract public static function label(): string;

    /**
     * @return array<int, Component>
     */
    abstract public static function fields(): array;

    abstract public static function view(): string;

    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return SchemaGenerator::schemaFromFields(static::fields(), static::mediaFields());
    }

    public static function description(): string
    {
        return '';
    }

    /**
     * @return array<int, string>
     */
    public static function mediaFields(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return SchemaGenerator::rulesFromFields(static::fields(), static::mediaFields());
    }
}
