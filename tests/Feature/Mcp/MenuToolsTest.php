<?php

use OursBlanc\Xms\Mcp\Tools\CreateMenuTool;
use OursBlanc\Xms\Mcp\Tools\GetMenuTool;
use OursBlanc\Xms\Mcp\Tools\UpdateMenuTool;
use OursBlanc\Xms\Mcp\XmsMcpServer;
use OursBlanc\Xms\Models\Menu;
use OursBlanc\Xms\Models\Page;
use OursBlanc\Xms\Tests\Feature\Mcp\McpToolTestCase;

uses(McpToolTestCase::class);

afterEach(fn () => Page::$authorResolver = null);

it('create_menu preserves language_switch, target, and display on every item', function () {
    $this->actingAsApiToken(['menus:read', 'menus:write']);

    XmsMcpServer::tool(CreateMenuTool::class, [
        'location' => 'header',
        'locale' => 'fr',
        'name' => 'Header nav',
        'items' => [
            [
                'label' => 'EN',
                'link_type' => 'language_switch',
                'target_locale' => 'en',
                'display' => 'link',
            ],
            [
                'label' => 'SmartXSP',
                'link_type' => 'url',
                'url' => 'https://smartxsp.io',
                'target' => '_blank',
                'display' => 'button_primary',
                'children' => [
                    ['label' => 'Formats', 'link_type' => 'url', 'url' => '#formats'],
                ],
            ],
        ],
    ])->assertOk();

    $menu = Menu::sole();

    expect($menu->items[0])
        ->toMatchArray(['label' => 'EN', 'link_type' => 'language_switch', 'target_locale' => 'en', 'display' => 'link']);

    expect($menu->items[1])
        ->toMatchArray(['label' => 'SmartXSP', 'link_type' => 'url', 'url' => 'https://smartxsp.io', 'target' => '_blank', 'display' => 'button_primary'])
        ->and($menu->items[1]['children'][0])->toMatchArray(['label' => 'Formats', 'url' => '#formats']);
});

it('update_menu preserves language_switch, target, and display when replacing items', function () {
    $this->actingAsApiToken(['menus:read', 'menus:write']);

    $menu = Menu::create([
        'location' => 'header', 'locale' => 'fr', 'name' => 'Header nav',
        'items' => [['label' => 'Old', 'link_type' => 'url', 'url' => '#old']],
    ]);

    XmsMcpServer::tool(UpdateMenuTool::class, [
        'id' => $menu->id,
        'items' => [
            ['label' => 'EN', 'link_type' => 'language_switch', 'target_locale' => 'en'],
            ['label' => 'Demo', 'link_type' => 'url', 'url' => '#demo', 'target' => '_blank', 'display' => 'button_secondary'],
        ],
    ])->assertOk();

    $menu->refresh();

    expect($menu->items[0])->toMatchArray(['label' => 'EN', 'link_type' => 'language_switch', 'target_locale' => 'en'])
        ->and($menu->items[1])->toMatchArray(['label' => 'Demo', 'target' => '_blank', 'display' => 'button_secondary']);
});

it('get_menu returns the raw language_switch/target/display fields as stored', function () {
    $this->actingAsApiToken(['menus:read', 'menus:write']);

    Menu::create([
        'location' => 'header', 'locale' => 'fr', 'name' => 'Header nav',
        'items' => [
            ['label' => 'EN', 'link_type' => 'language_switch', 'target_locale' => 'en'],
            ['label' => 'Demo', 'link_type' => 'url', 'url' => '#demo', 'target' => '_blank', 'display' => 'button_primary'],
        ],
    ]);

    XmsMcpServer::tool(GetMenuTool::class, ['location' => 'header', 'locale' => 'fr'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('items.0.link_type', 'language_switch')
            ->where('items.0.target_locale', 'en')
            ->where('items.1.target', '_blank')
            ->where('items.1.display', 'button_primary')
            ->etc());
});
