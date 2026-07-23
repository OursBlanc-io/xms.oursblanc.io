<?php

use Illuminate\Support\Facades\Http;
use OursBlanc\Xms\Mcp\Tools\AttachPageIllustrationTool;
use OursBlanc\Xms\Mcp\Tools\CreatePageTool;
use OursBlanc\Xms\Mcp\Tools\GetPageTool;
use OursBlanc\Xms\Mcp\Tools\UpdatePageTool;
use OursBlanc\Xms\Mcp\XmsMcpServer;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Tests\Feature\Mcp\McpToolTestCase;

uses(McpToolTestCase::class);

afterEach(fn () => Page::$authorResolver = null);

it('create_page accepts list_title, list_excerpt, and meta', function () {
    $this->actingAsApiToken();

    XmsMcpServer::tool(CreatePageTool::class, [
        'locale' => 'fr', 'title' => 'Full Title', 'slug' => 'demo',
        'blocks' => [['type' => 'text', 'data' => ['content' => 'Hello']]],
        'list_title' => 'Card Title',
        'list_excerpt' => 'A short summary.',
        'meta' => ['format' => 'video', 'visual_type' => 'portrait'],
    ])->assertOk();

    $page = Page::sole();

    expect($page->list_title)->toBe('Card Title')
        ->and($page->list_excerpt)->toBe('A short summary.')
        ->and($page->meta)->toBe(['format' => 'video', 'visual_type' => 'portrait']);
});

it('update_page replaces meta entirely', function () {
    $this->actingAsApiToken();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'demo', 'title' => 'X', 'blocks' => [], 'seo' => [],
        'meta' => ['format' => 'video'],
    ]);

    XmsMcpServer::tool(UpdatePageTool::class, [
        'id' => $page->id,
        'meta' => ['format' => 'display'],
    ])->assertOk();

    expect($page->fresh()->meta)->toBe(['format' => 'display']);
});

it('get_page exposes list_title, list_excerpt, meta, and illustration_url', function () {
    $this->actingAsApiToken();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'demo', 'title' => 'X', 'blocks' => [], 'seo' => [],
        'list_title' => 'Card Title', 'list_excerpt' => 'Summary', 'meta' => ['format' => 'video'],
    ]);

    XmsMcpServer::tool(GetPageTool::class, ['id' => $page->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('list_title', 'Card Title')
            ->where('list_excerpt', 'Summary')
            ->where('meta.format', 'video')
            ->where('illustration_url', null)
            ->etc());
});

it('attach_page_illustration downloads and attaches an image to the illustration collection', function () {
    $this->actingAsApiToken();

    // SsrfGuard resolves the host itself (no DNS available in the test
    // sandbox for a real domain) — a bare public IP short-circuits that via
    // filter_var(), matching how the private-IP rejection test below works.
    Http::fake(['8.8.8.8/*' => Http::response(str_repeat('x', 100), 200, ['Content-Type' => 'image/png'])]);

    $page = Page::create(['locale' => 'fr', 'slug' => 'demo', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(AttachPageIllustrationTool::class, [
        'page_id' => $page->id,
        'url' => 'https://8.8.8.8/cover.png',
    ])->assertOk();

    expect($page->fresh()->illustrationUrl())->not->toBeNull();
});

it('attach_page_illustration rejects an unsupported content type', function () {
    $this->actingAsApiToken();

    Http::fake(['8.8.8.8/*' => Http::response('not an image', 200, ['Content-Type' => 'application/pdf'])]);

    $page = Page::create(['locale' => 'fr', 'slug' => 'demo', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(AttachPageIllustrationTool::class, [
        'page_id' => $page->id,
        'url' => 'https://8.8.8.8/doc.pdf',
    ])->assertHasErrors(['Unsupported content type']);
});

it('attach_page_illustration rejects a private/internal url', function () {
    $this->actingAsApiToken();

    $page = Page::create(['locale' => 'fr', 'slug' => 'demo', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(AttachPageIllustrationTool::class, [
        'page_id' => $page->id,
        'url' => 'http://169.254.169.254/latest/meta-data/',
    ])->assertHasErrors(['disallowed private/internal address']);
});
