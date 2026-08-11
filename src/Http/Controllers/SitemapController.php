<?php

namespace OursBlanc\Xms\Http\Controllers;

use Illuminate\Http\Response;
use OursBlanc\Xms\Models\Page;

class SitemapController
{
    public function __invoke(): Response
    {
        $groups = Page::query()
            ->published()
            ->orderBy('updated_at', 'desc')
            ->get()
            ->reject(fn (Page $page): bool => str_contains($page->seo['robots'] ?? '', 'noindex'))
            ->groupBy('translation_group_id');

        return response()
            ->view('xms::sitemap', ['groups' => $groups])
            ->header('Content-Type', 'application/xml');
    }
}
