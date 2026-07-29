<?php

namespace OursBlanc\Xms\Blocks\Generic;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use OursBlanc\Xms\Blocks\Block;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Rendering\PageRenderer;

class TabbedShowcaseBlock extends Block
{
    public static function name(): string
    {
        return 'tabbed-showcase';
    }

    public static function label(): string
    {
        return 'Tabbed Showcase';
    }

    public static function description(): string
    {
        return 'A tab list where each tab reveals its own content, built from any other block(s).';
    }

    public static function fields(): array
    {
        return [
            TextInput::make('eyebrow')
                ->required(),
            TextInput::make('title')
                ->required(),
            TextInput::make('subtitle')
                ->required(),
            TextInput::make('anchor_id')
                ->label('Anchor ID (optional)')
                ->helperText("For in-page links, e.g. 'showcase'."),
            Repeater::make('tabs')
                ->schema([
                    TextInput::make('title')
                        ->label('Tab label')
                        ->required(),
                    TextInput::make('description')
                        ->label('Tab caption (optional)'),
                    Builder::make('content')
                        ->label('Tab content')
                        ->blocks(app(BlockRegistry::class)->builderBlocks(except: [self::name()]))
                        ->collapsible()
                        ->blockNumbers(false)
                        ->addActionLabel('Add block')
                        ->minItems(1),
                ])
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->collapsible()
                ->collapsed()
                ->minItems(1)
                ->maxItems(8)
                ->required(),
        ];
    }

    public static function nestedBlockFields(): array
    {
        return ['tabs.*.content'];
    }

    public static function view(): string
    {
        return 'xms::blocks.tabbed-showcase';
    }

    public static function resolveData(array $data, Page $page): array
    {
        $renderer = app(PageRenderer::class);

        $data['tabs'] = collect($data['tabs'] ?? [])
            ->map(function (array $tab) use ($renderer, $page) {
                $tab['resolved_content'] = $renderer->resolveBlocks($tab['content'] ?? [], $page);

                return $tab;
            })
            ->all();

        return $data;
    }
}
