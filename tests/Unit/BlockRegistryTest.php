<?php

use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Blocks\Generic\HeroBlock;

it('registers and finds a block by its machine name', function () {
    $registry = new BlockRegistry;
    $registry->register(HeroBlock::class);

    expect($registry->has('hero'))->toBeTrue()
        ->and($registry->find('hero'))->toBe(HeroBlock::class)
        ->and($registry->find('unknown'))->toBeNull();
});

it('rejects a class that does not extend Block', function () {
    $registry = new BlockRegistry;

    expect(fn () => $registry->register(stdClass::class))
        ->toThrow(InvalidArgumentException::class);
});

it('exposes all registered blocks', function () {
    $registry = new BlockRegistry;
    $registry->register(HeroBlock::class);

    expect($registry->all())->toBe(['hero' => HeroBlock::class]);
});

it('resolves the generic blocks registered by the service provider', function () {
    /** @var BlockRegistry $registry */
    $registry = app(BlockRegistry::class);

    foreach (['heading', 'text', 'hero', 'image', 'gallery', 'video', 'cta', 'columns'] as $name) {
        expect($registry->has($name))->toBeTrue("Expected block \"{$name}\" to be registered.");
    }
});

it('builds a list_block_types style payload via schemas()', function () {
    $registry = new BlockRegistry;
    $registry->register(HeroBlock::class);

    $schemas = $registry->schemas();

    expect($schemas)->toHaveKey('hero')
        ->and($schemas['hero'])->toHaveKeys(['name', 'label', 'description', 'schema', 'media_fields'])
        ->and($schemas['hero']['media_fields'])->toBe(['image']);
});
