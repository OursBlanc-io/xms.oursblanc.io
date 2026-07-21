<?php

use OursBlanc\Xms\Blocks\BuilderStateTransformer;

it('moves the stored uuid into the block data for the Builder form state', function () {
    $stored = [
        ['uuid' => 'abc', 'type' => 'hero', 'data' => ['title' => 'Hello']],
    ];

    $builderState = BuilderStateTransformer::toBuilderState($stored);

    expect($builderState)->toBe([
        ['type' => 'hero', 'data' => ['title' => 'Hello', 'uuid' => 'abc']],
    ]);
});

it('moves the uuid back out of the data when persisting the Builder state', function () {
    $builderState = [
        ['type' => 'hero', 'data' => ['title' => 'Hello', 'uuid' => 'abc']],
    ];

    $stored = BuilderStateTransformer::toStoredState($builderState);

    expect($stored)->toBe([
        ['uuid' => 'abc', 'type' => 'hero', 'data' => ['title' => 'Hello']],
    ]);
});

it('generates a fresh uuid for a new block with none yet', function () {
    $builderState = [
        ['type' => 'hero', 'data' => ['title' => 'Hello']],
    ];

    $stored = BuilderStateTransformer::toStoredState($builderState);

    expect($stored[0]['uuid'])->toBeString()->not->toBeEmpty();
});

it('round-trips without losing or mutating data', function () {
    $stored = [
        ['uuid' => 'a', 'type' => 'hero', 'data' => ['title' => 'One']],
        ['uuid' => 'b', 'type' => 'text', 'data' => ['content' => 'Two']],
    ];

    $roundTripped = BuilderStateTransformer::toStoredState(
        BuilderStateTransformer::toBuilderState($stored)
    );

    expect($roundTripped)->toBe($stored);
});
