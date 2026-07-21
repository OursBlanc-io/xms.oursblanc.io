<?php

namespace OursBlanc\Xms\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use OursBlanc\Xms\Filament\Resources\ApiTokenResource;
use OursBlanc\Xms\Filament\Resources\CodeSnippetResource;
use OursBlanc\Xms\Filament\Resources\MenuResource;
use OursBlanc\Xms\Filament\Resources\PageResource;

class XmsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'xms';
    }

    public static function make(): static
    {
        return new static;
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PageResource::class,
            MenuResource::class,
            CodeSnippetResource::class,
            ApiTokenResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
