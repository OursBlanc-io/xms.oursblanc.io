<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;

class AuroraHeroBlock extends Block
{
    public static function name(): string
    {
        return 'aurora-hero';
    }

    public static function label(): string
    {
        return 'Aurora Hero';
    }

    public static function description(): string
    {
        return 'Full-bleed animated hero with aurora background, kinetic headline, CTAs and a trust marquee.';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('eyebrow')
                ->required(),
            TextInput::make('title_lead')
                ->label('Title — plain part')
                ->required(),
            TextInput::make('title_accent')
                ->label('Title — accent part')
                ->required(),
            TextInput::make('lede')
                ->label('Lede paragraph')
                ->required(),
            TextInput::make('cta_primary_label')
                ->required(),
            TextInput::make('cta_primary_url')
                ->regex('/^(#[^\s]*|\/[^\s]*|https?:\/\/[^\s]+)$/')
                ->required(),
            TextInput::make('cta_secondary_label'),
            TextInput::make('cta_secondary_url')
                ->regex('/^(#[^\s]*|\/[^\s]*|https?:\/\/[^\s]+)$/'),
            TextInput::make('trust_label'),
            Repeater::make('trust_logos')
                ->schema([
                    TextInput::make('name')->required(),
                ])
                ->addActionLabel('Add logo'),
        ];
    }

    public static function view(): string
    {
        return 'xms::blocks.aurora-hero';
    }
}
