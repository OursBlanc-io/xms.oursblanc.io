<?php

namespace OursBlanc\Xms\Rendering;

use Illuminate\Contracts\View\View;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Models\Page;

class PageRenderer
{
    public function __construct(
        protected BlockRegistry $registry,
        protected ViewResolver $viewResolver,
    ) {}

    public function render(Page $page): View
    {
        return view($this->viewResolver->layout($page->template), [
            'page' => $page,
            'blocks' => $this->resolveBlocks($page->blocks ?? [], $page),
        ]);
    }

    /**
     * Resolves a list of blocks (top-level page blocks, or a nested-blocks
     * field's own array — see Block::nestedBlockFields()) into their view
     * path + rendered data. Public so a block's own Blade view can resolve
     * blocks it nests itself (e.g. Tabbed Showcase's per-tab content).
     *
     * @param  array<int, array{uuid: string, type: string, data: array<string, mixed>}>  $blocks
     * @return array<int, array{uuid: string, type: string, view: ?string, data: array<string, mixed>}>
     */
    public function resolveBlocks(array $blocks, Page $page): array
    {
        return collect($blocks)
            ->map(fn (array $block) => $this->resolveBlock($block, $page))
            ->all();
    }

    /**
     * @param  array{uuid: string, type: string, data: array<string, mixed>}  $block
     * @return array{uuid: string, type: string, view: ?string, data: array<string, mixed>}
     */
    public function resolveBlock(array $block, Page $page): array
    {
        $blockClass = $this->registry->find($block['type']);

        return [
            'uuid' => $block['uuid'],
            'type' => $block['type'],
            'view' => $blockClass ? $this->viewResolver->blockView($blockClass) : null,
            'data' => $blockClass ? $blockClass::resolveData($block['data'] ?? [], $page) : ($block['data'] ?? []),
        ];
    }
}
