<?php

use Illuminate\Validation\ValidationException;
use OursBlanc\Xms\Blocks\BlockRegistry;
use OursBlanc\Xms\Blocks\BlockValidator;
use OursBlanc\Xms\Blocks\Generic\HeroBlock;

beforeEach(function () {
    $this->registry = new BlockRegistry;
    $this->registry->register(HeroBlock::class);
    $this->validator = new BlockValidator($this->registry);
});

it('passes for a valid block payload', function () {
    $this->validator->validateBlock([
        'uuid' => 'abc-123',
        'type' => 'hero',
        'data' => [
            'title' => 'SmartSkin',
            'alignment' => 'left',
        ],
    ]);
})->throwsNoExceptions();

it('rejects an unknown block type', function () {
    $this->validator->validateBlock([
        'uuid' => 'abc-123',
        'type' => 'not-a-real-block',
        'data' => [],
    ]);
})->throws(ValidationException::class);

it('rejects a payload missing a required field', function () {
    $this->validator->validateBlock([
        'uuid' => 'abc-123',
        'type' => 'hero',
        'data' => [
            'alignment' => 'left',
        ],
    ]);
})->throws(ValidationException::class);

it('prefixes errors with the block index when validating a full blocks array', function () {
    try {
        $this->validator->validateBlocks([
            ['uuid' => 'a', 'type' => 'hero', 'data' => ['title' => 'OK', 'alignment' => 'left']],
            ['uuid' => 'b', 'type' => 'hero', 'data' => ['alignment' => 'left']],
        ]);

        expect(false)->toBeTrue('Expected a ValidationException to be thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('blocks.1.data.title');
    }
});
