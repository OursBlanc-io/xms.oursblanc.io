<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Models\Category;
use OursBlanc\Xms\Models\Page;

beforeEach(function () {
    config([
        'xms.locales' => ['fr'],
        'xms.default_locale' => 'fr',
        'xms.locale_in_url' => true,
        'xms.default_locale_hidden' => true,
    ]);
});

function makeListedPage(string $slug, string $title, ?int $categoryId = null, ?Carbon $publishedAt = null, array $meta = []): Page
{
    $page = Page::create([
        'locale' => 'fr',
        'slug' => $slug,
        'title' => $title,
        'blocks' => [],
        'seo' => [],
        'meta' => $meta,
        'status' => 'published',
        'published_at' => $publishedAt ?? now(),
    ]);

    if ($categoryId) {
        $page->categories()->sync([$categoryId]);
    }

    return $page;
}

function makeBlogIndex(array $blockData): Page
{
    return Page::create([
        'locale' => 'fr',
        'slug' => 'blog',
        'title' => 'Blog',
        'blocks' => [
            ['uuid' => 'list', 'type' => 'page_list', 'data' => $blockData],
        ],
        'seo' => [],
        'status' => 'published',
        'published_at' => now(),
    ]);
}

it('lists published pages', function () {
    makeListedPage('post-1', 'Post One');
    makeListedPage('post-2', 'Post Two');
    makeBlogIndex(['per_page' => 10]);

    $this->get('/blog')
        ->assertOk()
        ->assertSee('Post One')
        ->assertSee('Post Two');
});

it('excludes the listing page itself from its own results', function () {
    makeBlogIndex(['per_page' => 10]);

    $response = $this->get('/blog')->assertOk();

    expect($response->getContent())->toContain('No pages found.');
});

it('excludes draft pages from the listing', function () {
    makeListedPage('post-1', 'Post One');
    Page::create([
        'locale' => 'fr', 'slug' => 'draft-post', 'title' => 'Draft Post',
        'blocks' => [], 'seo' => [], 'status' => 'draft',
    ]);
    makeBlogIndex(['per_page' => 10]);

    $response = $this->get('/blog')->assertOk()->assertSee('Post One');

    expect($response->getContent())->not->toContain('Draft Post');
});

it('filters the listing by category', function () {
    $blog = Category::create(['name' => 'Blog', 'slug' => 'blog']);
    $news = Category::create(['name' => 'News', 'slug' => 'news']);

    makeListedPage('post-1', 'Blog Post', $blog->id);
    makeListedPage('post-2', 'News Post', $news->id);
    makeBlogIndex(['per_page' => 10, 'category' => 'blog']);

    $response = $this->get('/blog')->assertOk()->assertSee('Blog Post');

    expect($response->getContent())->not->toContain('News Post');
});

it('paginates the listing via the ?page= query string', function () {
    makeListedPage('post-1', 'Post One', publishedAt: now()->subMinutes(3));
    makeListedPage('post-2', 'Post Two', publishedAt: now()->subMinutes(2));
    makeListedPage('post-3', 'Post Three', publishedAt: now()->subMinute());
    makeBlogIndex(['per_page' => 2]);

    $this->get('/blog')
        ->assertOk()
        ->assertSee('Post Three')
        ->assertSee('Post Two')
        ->assertDontSee('Post One');

    $this->get('/blog?page=2')
        ->assertOk()
        ->assertSee('Post One')
        ->assertDontSee('Post Two')
        ->assertDontSee('Post Three');
});

it('filters the listing by a meta facet from the query string', function () {
    makeListedPage('post-1', 'Video Post', meta: ['format' => 'video']);
    makeListedPage('post-2', 'Display Post', meta: ['format' => 'display']);
    makeBlogIndex(['per_page' => 10, 'facets' => ['format']]);

    $this->get('/blog?format=video')
        ->assertOk()
        ->assertSee('Video Post')
        ->assertDontSee('Display Post');

    $this->get('/blog')
        ->assertOk()
        ->assertSee('Video Post')
        ->assertSee('Display Post');
});

it('combines a category filter and a meta facet filter', function () {
    $blog = Category::create(['name' => 'Blog', 'slug' => 'blog']);

    makeListedPage('post-1', 'Blog Video', $blog->id, meta: ['format' => 'video']);
    makeListedPage('post-2', 'Blog Display', $blog->id, meta: ['format' => 'display']);
    makeListedPage('post-3', 'Other Video', meta: ['format' => 'video']);
    makeBlogIndex(['per_page' => 10, 'category' => 'blog', 'facets' => ['format']]);

    $response = $this->get('/blog?format=video')->assertOk()->assertSee('Blog Video');

    expect($response->getContent())
        ->not->toContain('Blog Display')
        ->not->toContain('Other Video');
});

it('renders the illustration, list_title, and list_excerpt when set', function () {
    Storage::fake('public');
    Storage::disk('public')->put('xms-pending/cover.png', file_get_contents(__DIR__.'/../Fixtures/test-image.png'));

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'post-1', 'title' => 'Full Title',
        'list_title' => 'Card Title', 'list_excerpt' => 'A short summary.',
        'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);
    $page->addMediaFromDisk('xms-pending/cover.png', 'public')->toMediaCollection(Page::ILLUSTRATION_COLLECTION, 'public');

    makeBlogIndex(['per_page' => 10]);

    $response = $this->get('/blog')->assertOk()
        ->assertSee('Card Title')
        ->assertSee('A short summary.');

    expect($response->getContent())
        ->not->toContain('Full Title')
        ->toContain($page->fresh()->illustrationUrl());
});
