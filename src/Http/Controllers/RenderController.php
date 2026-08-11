<?php

namespace OursBlanc\Xms\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Rendering\PageRenderer;

class RenderController
{
    /**
     * Route parameters are read directly off the request rather than declared
     * as method arguments: the `{locale}/{slug?}` route and the root fallback
     * route place/name things differently (the fallback has no `slug`
     * parameter at all — see below), and Laravel's controller method
     * injection for plain scalars is positional, not name-matched — a fixed
     * signature would silently misread them depending on which route matched.
     */
    public function __invoke(Request $request, PageRenderer $renderer): View|RedirectResponse
    {
        $locale = $request->route('locale');

        // The `{locale}/{slug?}` route has a real `slug` parameter; the root
        // fallback route (see routes/web.php) doesn't — Route::fallback()
        // captures under its own internal parameter name, not `slug` — so
        // the request path is used directly there instead.
        $slug = $locale !== null
            ? ($request->route('slug') ?? '')
            : ltrim($request->path(), '/');

        // No `{locale}` segment matched — this is the root-level fallback,
        // which serves two distinct things: a locale-agnostic page (no
        // locale at all, checked first), or — when there's only one
        // effective locale (locale_in_url disabled, or the default locale
        // hidden from the URL) — that locale's own page.
        $page = $locale
            ? Page::query()->locale($locale)->where('slug', $slug)->published()->first()
            : Page::query()->locale(null)->where('slug', $slug)->published()->first();

        if (! $page && ! $locale && (config('xms.default_locale_hidden') || ! config('xms.locale_in_url'))) {
            $page = Page::query()
                ->locale(config('xms.default_locale'))
                ->where('slug', $slug)
                ->published()
                ->first();
        }

        // Bare `/` with nothing configured to serve there (no locale-
        // agnostic homepage, default locale not hidden) — redirect to the
        // visitor's best-matching locale instead of a dead-end 404. This is
        // the fallback the app's own `Route::get('/', ...)` used to provide,
        // but that route is registered after this package's own (Laravel
        // loads `withRouting()`'s app routes once every provider has
        // booted), so it can never actually be reached — handled here
        // instead, generically, for any app using XMS.
        if (! $page && ! $locale && $slug === '' && config('xms.locale_in_url') && config('xms.locales', []) !== []) {
            $preferred = $request->getPreferredLanguage(config('xms.locales'))
                ?? config('xms.default_locale');

            return redirect('/'.$preferred);
        }

        abort_if(! $page, 404);

        return $renderer->render($page);
    }
}
