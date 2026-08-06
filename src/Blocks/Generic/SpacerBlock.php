<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class SpacerBlock extends Block
{
    public static function name(): string
    {
        return 'spacer';
    }

    public static function label(): string
    {
        return 'Spacer';
    }

    public static function description(): string
    {
        return 'Blank vertical space, with an independent height for desktop and mobile.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('desktop_height')
                ->label('Height — desktop (px)')
                ->numeric()
                ->default(30)
                ->required(),
            TextInput::make('mobile_height')
                ->label('Height — mobile (px)')
                ->numeric()
                ->default(30)
                ->required(),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.spacer';
    }
}
