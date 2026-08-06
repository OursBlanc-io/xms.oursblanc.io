<?php

namespace OursBlanc\Xms\Blocks\Generic;

use OursBlanc\Xms\Blocks\Block;

class SeparatorBlock extends Block
{
    public static function name(): string
    {
        return 'separator';
    }

    public static function label(): string
    {
        return 'Separator';
    }

    public static function description(): string
    {
        return 'A horizontal rule (<hr>) dividing the sections above and below it.';
    }

    public static function fields(): array
    {
        return [];
    }

    public static function view(): string
    {
        return 'xms::blocks.separator';
    }
}
