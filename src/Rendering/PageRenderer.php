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
            'blocks' => $this->resolveBlocks($page),
        ]);
    }

    /**
     * @return array<int, array{uuid: string, type: string, view: ?string, data: array<string, mixed>}>
     */
    protected function resolveBlocks(Page $page): array
    {
        return collect($page->blocks ?? [])
            ->map(function (array $block) use ($page) {
                $blockClass = $this->registry->find($block['type']);

                return [
                    'uuid' => $block['uuid'],
                    'type' => $block['type'],
                    'view' => $blockClass ? $this->viewResolver->blockView($blockClass) : null,
                    'data' => $blockClass ? $blockClass::resolveData($block['data'] ?? [], $page) : ($block['data'] ?? []),
                ];
            })
            ->all();
    }
}
