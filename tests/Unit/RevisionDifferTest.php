<?php

use OursBlanc\Xms\Support\RevisionDiffer;

it('detects added blocks', function () {
    $diff = RevisionDiffer::diff(
        before: [['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '1']]],
        after: [
            ['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '1']],
            ['uuid' => 'b', 'type' => 'text', 'data' => ['content' => '2']],
        ],
    );

    expect($diff['added'])->toBe(['b'])
        ->and($diff['removed'])->toBe([])
        ->and($diff['modified'])->toBe([]);
});

it('detects removed blocks', function () {
    $diff = RevisionDiffer::diff(
        before: [
            ['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '1']],
            ['uuid' => 'b', 'type' => 'text', 'data' => ['content' => '2']],
        ],
        after: [['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '1']]],
    );

    expect($diff['removed'])->toBe(['b'])
        ->and($diff['added'])->toBe([]);
});

it('detects modified blocks by comparing their data', function () {
    $diff = RevisionDiffer::diff(
        before: [['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '1']]],
        after: [['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '2']]],
    );

    expect($diff['modified'])->toBe(['a']);
});

it('reports no differences for identical blocks', function () {
    $blocks = [['uuid' => 'a', 'type' => 'text', 'data' => ['content' => '1']]];

    $diff = RevisionDiffer::diff($blocks, $blocks);

    expect($diff)->toBe(['added' => [], 'removed' => [], 'modified' => []]);
});
