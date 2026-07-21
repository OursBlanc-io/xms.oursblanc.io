<?php

use OursBlanc\Xms\Support\BlockNormalizer;

it('generates a uuid for a block missing one', function () {
    $blocks = BlockNormalizer::normalize([
        ['type' => 'hero', 'data' => ['title' => 'X']],
    ]);

    expect($blocks[0]['uuid'])->toBeString()->not->toBeEmpty();
});

it('preserves an existing uuid', function () {
    $blocks = BlockNormalizer::normalize([
        ['uuid' => 'existing-uuid', 'type' => 'hero', 'data' => ['title' => 'X']],
    ]);

    expect($blocks[0]['uuid'])->toBe('existing-uuid');
});

it('defaults data to an empty array when omitted', function () {
    $blocks = BlockNormalizer::normalize([
        ['type' => 'text'],
    ]);

    expect($blocks[0]['data'])->toBe([]);
});

it('reindexes blocks sequentially', function () {
    $blocks = BlockNormalizer::normalize([
        5 => ['type' => 'text', 'data' => []],
        9 => ['type' => 'cta', 'data' => []],
    ]);

    expect(array_keys($blocks))->toBe([0, 1]);
});
