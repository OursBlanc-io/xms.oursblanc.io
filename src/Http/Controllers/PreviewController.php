<?php

namespace OursBlanc\Xms\Http\Controllers;

use Illuminate\Http\Response;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Rendering\PageRenderer;

class PreviewController
{
    public function __invoke(PageRenderer $renderer, Page $page): Response
    {
        abort_unless(request()->hasValidSignature(), 403);

        return response($renderer->render($page))
            ->header('Cache-Control', 'no-store');
    }
}
