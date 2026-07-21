<?php

namespace OursBlanc\Xms\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Rendering\PageRenderer;

class RenderController
{
    /**
     * Route parameters are read directly off the request rather than declared
     * as method arguments: the `{locale}/{slug?}` and `{slug?}` (with a
     * `locale` route default) variants place them in different positions in
     * the route's parameter list, and Laravel's controller method injection
     * for plain scalars is positional, not name-matched — a fixed signature
     * would silently swap them depending on which route matched.
     */
    public function __invoke(Request $request, PageRenderer $renderer): View
    {
        $locale = $request->route('locale') ?? config('xms.default_locale');
        $slug = $request->route('slug') ?? '';

        $page = Page::query()
            ->locale($locale)
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        return $renderer->render($page);
    }
}
