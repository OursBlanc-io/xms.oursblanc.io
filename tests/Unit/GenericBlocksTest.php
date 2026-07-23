<?php

use OursBlanc\Xms\Blocks\Generic\ColumnsBlock;
use OursBlanc\Xms\Blocks\Generic\CtaBlock;
use OursBlanc\Xms\Blocks\Generic\FormBlock;
use OursBlanc\Xms\Blocks\Generic\GalleryBlock;
use OursBlanc\Xms\Blocks\Generic\HeadingBlock;
use OursBlanc\Xms\Blocks\Generic\HeroBlock;
use OursBlanc\Xms\Blocks\Generic\ImageBlock;
use OursBlanc\Xms\Blocks\Generic\PageListBlock;
use OursBlanc\Xms\Blocks\Generic\TextBlock;
use OursBlanc\Xms\Blocks\Generic\VideoBlock;

dataset('generic_blocks', [
    'heading' => [HeadingBlock::class],
    'text' => [TextBlock::class],
    'hero' => [HeroBlock::class],
    'image' => [ImageBlock::class],
    'gallery' => [GalleryBlock::class],
    'video' => [VideoBlock::class],
    'cta' => [CtaBlock::class],
    'columns' => [ColumnsBlock::class],
    'page_list' => [PageListBlock::class],
    'form' => [FormBlock::class],
]);

it('exposes a complete, non-empty self-description for every generic block', function (string $blockClass) {
    expect($blockClass::name())->toBeString()->not->toBeEmpty()
        ->and($blockClass::label())->toBeString()->not->toBeEmpty()
        ->and($blockClass::description())->toBeString()->not->toBeEmpty()
        ->and($blockClass::view())->toStartWith('xms::blocks.');

    $schema = $blockClass::schema();

    expect($schema)->toHaveKey('type', 'object')
        ->and($schema['properties'])->not->toBeEmpty();

    $rules = $blockClass::rules();

    expect($rules)->toBeArray();
})->with('generic_blocks');

it('registers each generic block view as an existing Blade view', function (string $blockClass) {
    expect(view()->exists($blockClass::view()))->toBeTrue();
})->with('generic_blocks');
