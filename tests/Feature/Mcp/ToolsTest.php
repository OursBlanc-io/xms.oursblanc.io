<?php

use OursBlanc\Xms\Mcp\Tools\AttachMediaFromUrlTool;
use OursBlanc\Xms\Mcp\Tools\CreatePageTool;
use OursBlanc\Xms\Mcp\Tools\GetPageTool;
use OursBlanc\Xms\Mcp\Tools\ListBlockTypesTool;
use OursBlanc\Xms\Mcp\Tools\ListPagesTool;
use OursBlanc\Xms\Mcp\Tools\PatchBlocksTool;
use OursBlanc\Xms\Mcp\Tools\PublishPageTool;
use OursBlanc\Xms\Mcp\Tools\TranslatePageTool;
use OursBlanc\Xms\Mcp\Tools\UnpublishPageTool;
use OursBlanc\Xms\Mcp\Tools\UpdatePageTool;
use OursBlanc\Xms\Mcp\XmsMcpServer;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Tests\Feature\Mcp\McpToolTestCase;

uses(McpToolTestCase::class);

afterEach(fn () => Page::$authorResolver = null);

it('list_block_types returns every generic block with its schema', function () {
    $this->actingAsApiToken();

    XmsMcpServer::tool(ListBlockTypesTool::class)
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('block_types', 8)->etc());
});

it('list_pages filters by locale, status, and search', function () {
    $this->actingAsApiToken();

    Page::create(['locale' => 'fr', 'slug' => 'a', 'title' => 'Alpha', 'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now()]);
    Page::create(['locale' => 'fr', 'slug' => 'b', 'title' => 'Beta', 'blocks' => [], 'seo' => [], 'status' => 'draft']);
    Page::create(['locale' => 'en', 'slug' => 'c', 'title' => 'Gamma', 'blocks' => [], 'seo' => [], 'status' => 'published', 'published_at' => now()]);

    XmsMcpServer::tool(ListPagesTool::class, ['locale' => 'fr', 'status' => 'published'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('pages', 1)
            ->where('pages.0.slug', 'a')
            ->etc());
});

it('get_page returns the full page with urls and sibling locales', function () {
    $this->actingAsApiToken();

    $fr = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'Accueil',
        'blocks' => [['uuid' => 'u1', 'type' => 'text', 'data' => ['content' => 'Bonjour']]],
        'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);
    Page::create(['locale' => 'en', 'slug' => 'home', 'title' => 'Home', 'translation_group_id' => $fr->translation_group_id, 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(GetPageTool::class, ['id' => $fr->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('id', $fr->id)
            ->where('blocks.0.uuid', 'u1')
            ->has('urls.public')
            ->has('urls.preview')
            ->has('sibling_locales', 1)
            ->etc());
});

it('get_page returns an actionable error for an unknown id', function () {
    $this->actingAsApiToken();

    XmsMcpServer::tool(GetPageTool::class, ['id' => 999])
        ->assertHasErrors(['No page with id [999]']);
});

it('create_page validates each block against its type schema and reports the offending field', function () {
    $this->actingAsApiToken();

    XmsMcpServer::tool(CreatePageTool::class, [
        'locale' => 'fr',
        'title' => 'Accueil',
        'slug' => 'accueil',
        'blocks' => [
            ['type' => 'hero', 'data' => ['alignment' => 'left']], // missing required "title"
        ],
    ])->assertHasErrors(['blocks.0.data.title']);

    expect(Page::count())->toBe(0);
});

it('create_page creates a draft page with generated uuids and returns preview/public urls', function () {
    $this->actingAsApiToken();

    $response = XmsMcpServer::tool(CreatePageTool::class, [
        'locale' => 'fr',
        'title' => 'Accueil',
        'slug' => 'accueil',
        'blocks' => [
            ['type' => 'hero', 'data' => ['title' => 'Bienvenue', 'alignment' => 'left']],
        ],
    ])->assertOk();

    $page = Page::sole();

    expect($page->status)->toBe('draft')
        ->and($page->blocks[0]['uuid'])->toBeString()->not->toBeEmpty();

    $response->assertStructuredContent(fn ($json) => $json
        ->where('id', $page->id)
        ->where('status', 'draft')
        ->has('urls.preview')
        ->etc());
});

it('create_page allows an empty slug (the locale homepage)', function () {
    $this->actingAsApiToken();

    XmsMcpServer::tool(CreatePageTool::class, [
        'locale' => 'fr', 'title' => 'Accueil', 'slug' => '',
        'blocks' => [['type' => 'text', 'data' => ['content' => 'Bienvenue']]],
    ])->assertOk();

    expect(Page::sole()->slug)->toBe('');
});

it('create_page rejects a slug already used in the same locale', function () {
    $this->actingAsApiToken();

    Page::create(['locale' => 'fr', 'slug' => 'accueil', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(CreatePageTool::class, [
        'locale' => 'fr', 'title' => 'Y', 'slug' => 'accueil', 'blocks' => [],
    ])->assertHasErrors(['slug']);
});

it('update_page only changes the fields provided and preserves existing block uuids', function () {
    $this->actingAsApiToken();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'Old title',
        'blocks' => [['uuid' => 'stable-uuid', 'type' => 'text', 'data' => ['content' => 'A']]],
        'seo' => [],
    ]);

    XmsMcpServer::tool(UpdatePageTool::class, ['id' => $page->id, 'title' => 'New title'])->assertOk();

    $page->refresh();

    expect($page->title)->toBe('New title')
        ->and($page->slug)->toBe('accueil')
        ->and($page->blocks[0]['uuid'])->toBe('stable-uuid');
});

it('update_page reports a structured error for invalid block data', function () {
    $this->actingAsApiToken();

    $page = Page::create(['locale' => 'fr', 'slug' => 'accueil', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(UpdatePageTool::class, [
        'id' => $page->id,
        'blocks' => [['type' => 'hero', 'data' => []]],
    ])->assertHasErrors(['blocks.0.data.title']);
});

it('patch_blocks inserts, updates, moves, and removes blocks in order', function () {
    $this->actingAsApiToken();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [
            ['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '1']],
            ['uuid' => 'b', 'type' => 'text', 'data' => ['content' => '2']],
        ],
        'seo' => [],
    ]);

    XmsMcpServer::tool(PatchBlocksTool::class, [
        'id' => $page->id,
        'operations' => [
            ['op' => 'update', 'uuid' => 'a', 'data' => ['content' => '1-updated']],
            ['op' => 'insert', 'position' => 1, 'block' => ['type' => 'text', 'data' => ['content' => 'new']]],
            ['op' => 'remove', 'uuid' => 'b'],
        ],
    ])->assertOk();

    $blocks = $page->fresh()->blocks;

    expect($blocks)->toHaveCount(2)
        ->and($blocks[0]['data']['content'])->toBe('1-updated')
        ->and($blocks[1]['data']['content'])->toBe('new');
});

it('patch_blocks reports an explicit error for an unknown uuid', function () {
    $this->actingAsApiToken();

    $page = Page::create(['locale' => 'fr', 'slug' => 'accueil', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(PatchBlocksTool::class, [
        'id' => $page->id,
        'operations' => [['op' => 'update', 'uuid' => 'does-not-exist', 'data' => []]],
    ])->assertHasErrors(['no block with uuid']);
});

it('translate_page rejects mismatched block structure with an explicit error', function () {
    $this->actingAsApiToken();

    $source = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [
            ['uuid' => 'a', 'type' => 'heading', 'data' => ['level' => 'h1', 'text' => 'Bonjour']],
            ['uuid' => 'b', 'type' => 'text', 'data' => ['content' => 'Texte']],
        ],
        'seo' => [],
    ]);

    XmsMcpServer::tool(TranslatePageTool::class, [
        'id' => $source->id,
        'target_locale' => 'en',
        'title' => 'Home',
        'slug' => 'home',
        'blocks_translated' => [
            ['type' => 'text', 'data' => ['content' => 'Text']], // wrong order/type vs source
        ],
    ])->assertHasErrors(['same block types in the same order']);
});

it('translate_page creates a sibling page preserving block uuids', function () {
    $this->actingAsApiToken();

    $source = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'Accueil',
        'blocks' => [
            ['uuid' => 'a', 'type' => 'heading', 'data' => ['level' => 'h1', 'text' => 'Bonjour']],
        ],
        'seo' => [], 'status' => 'published', 'published_at' => now(),
    ]);

    XmsMcpServer::tool(TranslatePageTool::class, [
        'id' => $source->id,
        'target_locale' => 'en',
        'title' => 'Home',
        'slug' => 'home',
        'blocks_translated' => [
            ['type' => 'heading', 'data' => ['level' => 'h1', 'text' => 'Hello']],
        ],
    ])->assertOk();

    $translated = Page::where('locale', 'en')->sole();

    expect($translated->translation_group_id)->toBe($source->translation_group_id)
        ->and($translated->status)->toBe('draft')
        ->and($translated->blocks[0]['uuid'])->toBe('a')
        ->and($translated->blocks[0]['data']['text'])->toBe('Hello');
});

it('publish_page and unpublish_page flip the status and record a revision', function () {
    $this->actingAsApiToken();

    $page = Page::create(['locale' => 'fr', 'slug' => 'accueil', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(PublishPageTool::class, ['id' => $page->id])->assertOk();
    expect($page->fresh()->status)->toBe('published');

    XmsMcpServer::tool(UnpublishPageTool::class, ['id' => $page->id])->assertOk();
    expect($page->fresh()->status)->toBe('draft');
});

it('every write attributes its revision to the acting api token', function () {
    $apiToken = $this->actingAsApiToken();

    $page = Page::create(['locale' => 'fr', 'slug' => 'accueil', 'title' => 'X', 'blocks' => [], 'seo' => []]);

    XmsMcpServer::tool(UpdatePageTool::class, ['id' => $page->id, 'title' => 'Y'])->assertOk();

    $revision = $page->revisions()->first();

    expect($revision->author_type)->toBe('api_token')
        ->and($revision->author_id)->toBe((string) $apiToken->id);
});

it('attach_media_from_url rejects a field that is not a media field on the block type', function () {
    $this->actingAsApiToken();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [['uuid' => 'a', 'type' => 'hero', 'data' => ['title' => 'X', 'alignment' => 'left']]],
        'seo' => [],
    ]);

    XmsMcpServer::tool(AttachMediaFromUrlTool::class, [
        'page_id' => $page->id,
        'block_uuid' => 'a',
        'field' => 'title',
        'url' => 'https://example.com/image.png',
    ])->assertHasErrors(['not a direct media field']);
});

it('attach_media_from_url rejects a private/internal url', function () {
    $this->actingAsApiToken();

    $page = Page::create([
        'locale' => 'fr', 'slug' => 'accueil', 'title' => 'X',
        'blocks' => [['uuid' => 'a', 'type' => 'hero', 'data' => ['title' => 'X', 'alignment' => 'left']]],
        'seo' => [],
    ]);

    XmsMcpServer::tool(AttachMediaFromUrlTool::class, [
        'page_id' => $page->id,
        'block_uuid' => 'a',
        'field' => 'image',
        'url' => 'http://169.254.169.254/latest/meta-data/',
    ])->assertHasErrors(['disallowed private/internal address']);
});
