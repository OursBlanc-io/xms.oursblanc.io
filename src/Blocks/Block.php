<?php

namespace OursBlanc\Xms\Blocks;

use Filament\Forms\Components\Component;
use OursBlanc\Xms\Models\Page;

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

    /**
     * Runs just before rendering, letting a block compute derived data (e.g.
     * querying the database) instead of relying solely on its stored `data`.
     * Most blocks are purely presentational and never need to override this.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function resolveData(array $data, Page $page): array
    {
        return $data;
    }

    /**
     * Map of video media field name => poster media field name. When a video
     * uploaded through the mapped field has no poster set, XMS attempts to
     * generate one automatically (see Media\VideoProcessor).
     *
     * @return array<string, string>
     */
    public static function posterFieldMap(): array
    {
        return [];
    }
}
