<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use OursBlanc\Xms\Cache\CacheInvalidator;
use OursBlanc\Xms\Jobs\PurgeCdnCacheJob;
use OursBlanc\Xms\Mcp\Tools\AttachMediaFromUrlTool;
use OursBlanc\Xms\Mcp\Tools\CreatePageTool;
use OursBlanc\Xms\Mcp\Tools\ListBlockTypesTool;
use OursBlanc\Xms\Mcp\Tools\PatchBlocksTool;
use OursBlanc\Xms\Mcp\Tools\PublishPageTool;
use OursBlanc\Xms\Mcp\XmsMcpServer;
use OursBlanc\Xms\Models\ApiToken;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Models\PageRevision;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * The scenario from the dev plan's MCP section, taken as the definition of
 * the project's success: a simulated MCP client calls list_block_types,
 * builds a 5-block page, creates it, edits it via patch_blocks, attaches an
 * image by URL, publishes it, and we verify the public HTTP render contains
 * the content and the cache invalidator received the right URLs.
 */
it('runs the full end-to-end MCP scenario: discover, create, patch, attach media, publish, render, purge', function () {
    Queue::fake();
    Http::fake([
        'example.com/*' => Http::response(file_get_contents(__DIR__.'/../../Fixtures/test-image.png'), 200, [
            'Content-Type' => 'image/png',
        ]),
    ]);

    ['token' => $apiToken, 'plainTextToken' => $plainTextToken] = ApiToken::generate(
        'Claude',
        ['pages:read', 'pages:write', 'pages:publish'],
    );

    app()->instance(ApiToken::class, $apiToken);
    Page::$authorResolver = fn () => ['type' => 'api_token', 'id' => (string) $apiToken->id];

    // 1. Discover the available block types.
    $blockTypes = XmsMcpServer::tool(ListBlockTypesTool::class)->assertOk();
    $blockTypes->assertStructuredContent(fn ($json) => $json->has('block_types', 8)->etc());

    // 2. Build and create a 5-block page.
    $created = XmsMcpServer::tool(CreatePageTool::class, [
        'locale' => 'fr',
        'title' => 'SmartSkin',
        'slug' => 'produits/smartskin',
        'blocks' => [
            ['type' => 'hero', 'data' => ['title' => 'SmartSkin', 'alignment' => 'center']],
            ['type' => 'heading', 'data' => ['level' => 'h2', 'text' => 'Presentation']],
            ['type' => 'text', 'data' => ['content' => 'Un capteur cutane intelligent.']],
            ['type' => 'cta', 'data' => [
                'title' => 'Interesse ?', 'button_label' => 'Contact', 'button_url' => '/contact', 'style' => 'primary',
            ]],
            ['type' => 'columns', 'data' => ['columns' => [
                ['title' => 'Colonne A', 'content' => 'Texte A'],
                ['title' => 'Colonne B', 'content' => 'Texte B'],
            ]]],
        ],
        'seo' => ['title' => 'SmartSkin | OursBlanc', 'description' => 'Capteur cutane intelligent.'],
    ])->assertOk();

    $page = Page::sole();

    expect($page->status)->toBe('draft')
        ->and($page->blocks)->toHaveCount(5);

    $heroUuid = $page->blocks[0]['uuid'];

    // 3. Edit it via patch_blocks: tweak the hero title and insert a new block.
    XmsMcpServer::tool(PatchBlocksTool::class, [
        'id' => $page->id,
        'operations' => [
            ['op' => 'update', 'uuid' => $heroUuid, 'data' => ['title' => 'SmartSkin Pro']],
            ['op' => 'insert', 'position' => 5, 'block' => [
                'type' => 'text', 'data' => ['content' => 'Disponible des maintenant.'],
            ]],
        ],
    ])->assertOk();

    $page->refresh();

    expect($page->blocks)->toHaveCount(6)
        ->and($page->blocks[0]['data']['title'])->toBe('SmartSkin Pro')
        ->and($page->blocks[5]['data']['content'])->toBe('Disponible des maintenant.');

    // 4. Attach an image to the hero block by URL (SSRF-guarded download).
    XmsMcpServer::tool(AttachMediaFromUrlTool::class, [
        'page_id' => $page->id,
        'block_uuid' => $heroUuid,
        'field' => 'image',
        'url' => 'https://example.com/smartskin.png',
    ])->assertOk();

    $page->refresh();
    $imageId = $page->blocks[0]['data']['image'];

    expect($imageId)->toBeInt();
    expect(Media::find($imageId))->not->toBeNull();

    // Every write so far was recorded as an api_token-authored revision.
    expect($page->revisions()->count())->toBeGreaterThanOrEqual(2)
        ->and(PageRevision::query()->pluck('author_type')->unique()->all())->toBe(['api_token']);

    // 5. Publish it.
    XmsMcpServer::tool(PublishPageTool::class, ['id' => $page->id])->assertOk();

    $page->refresh();
    expect($page->status)->toBe('published');

    // 6. The public HTTP render contains the (patched, media-attached) content.
    $response = test()->get('/produits/smartskin')->assertOk();

    $response->assertSee('SmartSkin Pro')
        ->assertSee('Presentation')
        ->assertSee('Un capteur cutane intelligent.')
        ->assertSee('Disponible des maintenant.')
        ->assertSee('Colonne A')
        ->assertSee('conversions', false); // the attached image's <picture> srcset

    // 7. The cache invalidator was asked to purge the right URLs.
    Queue::assertPushed(PurgeCdnCacheJob::class, function (PurgeCdnCacheJob $job) {
        return in_array(url('/produits/smartskin'), $job->urls, true)
            && in_array(url('/sitemap.xml'), $job->urls, true);
    });

    $invalidator = Mockery::mock(CacheInvalidator::class);
    $invalidator->shouldReceive('purgeUrls')->once()->with([url('/produits/smartskin'), url('/sitemap.xml')]);

    (new PurgeCdnCacheJob([url('/produits/smartskin'), url('/sitemap.xml')]))->handle($invalidator);
});
