<?php

use OursBlanc\Xms\Mcp\Tools\CreateCategoryTool;
use OursBlanc\Xms\Mcp\Tools\CreatePageTool;
use OursBlanc\Xms\Mcp\Tools\ListCategoriesTool;
use OursBlanc\Xms\Mcp\Tools\UpdatePageTool;
use OursBlanc\Xms\Mcp\XmsMcpServer;
use OursBlanc\Xms\Models\Category;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Tests\Feature\Mcp\McpToolTestCase;

uses(McpToolTestCase::class);

afterEach(fn () => Page::$authorResolver = null);

it('create_category derives the slug from the name when omitted', function () {
    $this->actingAsApiToken();

    XmsMcpServer::tool(CreateCategoryTool::class, ['name' => 'Actualités & Presse'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->where('slug', 'actualites-presse')->etc());

    expect(Category::query()->where('slug', 'actualites-presse')->exists())->toBeTrue();
});

it('list_categories returns every category ordered by name', function () {
    $this->actingAsApiToken();

    Category::create(['name' => 'Beta', 'slug' => 'beta']);
    Category::create(['name' => 'Alpha', 'slug' => 'alpha']);

    XmsMcpServer::tool(ListCategoriesTool::class)
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('categories', 2)
            ->where('categories.0.name', 'Alpha')
            ->etc());
});

it('create_page attaches the given category_ids', function () {
    $this->actingAsApiToken();

    $category = Category::create(['name' => 'Blog', 'slug' => 'blog']);

    XmsMcpServer::tool(CreatePageTool::class, [
        'locale' => 'fr', 'title' => 'Post', 'slug' => 'post',
        'blocks' => [['type' => 'text', 'data' => ['content' => 'Hello']]],
        'category_ids' => [$category->id],
    ])->assertOk();

    $page = Page::sole();

    expect($page->categories->pluck('id')->all())->toBe([$category->id]);
});

it('update_page replaces categories via category_ids', function () {
    $this->actingAsApiToken();

    $before = Category::create(['name' => 'Before', 'slug' => 'before']);
    $after = Category::create(['name' => 'After', 'slug' => 'after']);

    $page = Page::create(['locale' => 'fr', 'slug' => 'post', 'title' => 'Post', 'blocks' => [], 'seo' => []]);
    $page->categories()->sync([$before->id]);

    XmsMcpServer::tool(UpdatePageTool::class, ['id' => $page->id, 'category_ids' => [$after->id]])
        ->assertOk();

    expect($page->categories()->pluck('xms_categories.id')->all())->toBe([$after->id]);
});
