<?php

use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Media\MediaManagerService;

beforeEach(function () {
    Storage::fake('public');
    config(['xms.media_manager.disk' => 'public', 'xms.media_manager.root' => 'mediacontents']);
});

function mediaManager(): MediaManagerService
{
    return app(MediaManagerService::class);
}

it('creates the root directory on first use', function () {
    mediaManager()->fullPath();

    Storage::disk('public')->assertExists('mediacontents');
});

it('creates, lists, and deletes a folder', function () {
    $manager = mediaManager();

    expect($manager->createFolder('', 'brand'))->toBeTrue();
    expect($manager->folders())->toBe(['brand']);

    expect($manager->deleteFolder('', 'brand'))->toBeTrue();
    expect($manager->folders())->toBe([]);
});

it('refuses to create a folder that already exists', function () {
    $manager = mediaManager();
    $manager->createFolder('', 'brand');

    expect($manager->createFolder('', 'brand'))->toBeFalse();
});

it('lists files with their size and url', function () {
    $manager = mediaManager();
    Storage::disk('public')->put('mediacontents/logo.png', 'fake-content');

    $files = $manager->files();

    expect($files)->toHaveCount(1)
        ->and($files[0]['name'])->toBe('logo.png')
        ->and($files[0]['size'])->toBe(strlen('fake-content'));
});

it('renames a file, refusing when the target name is already taken', function () {
    $manager = mediaManager();
    Storage::disk('public')->put('mediacontents/logo.png', 'a');
    Storage::disk('public')->put('mediacontents/other.png', 'b');

    expect($manager->renameFile('', 'logo.png', 'brand-logo.png'))->toBeTrue();
    Storage::disk('public')->assertMissing('mediacontents/logo.png');
    Storage::disk('public')->assertExists('mediacontents/brand-logo.png');

    expect($manager->renameFile('', 'brand-logo.png', 'other.png'))->toBeFalse();
});

it('deletes a file', function () {
    $manager = mediaManager();
    Storage::disk('public')->put('mediacontents/logo.png', 'a');

    expect($manager->deleteFile('', 'logo.png'))->toBeTrue();
    Storage::disk('public')->assertMissing('mediacontents/logo.png');
});

it('operates within a nested sub-path', function () {
    $manager = mediaManager();
    $manager->createFolder('', 'brand');
    $manager->createFolder('brand', 'logos');
    Storage::disk('public')->put('mediacontents/brand/logos/logo.png', 'a');

    expect($manager->folders('brand'))->toBe(['logos'])
        ->and($manager->files('brand/logos'))->toHaveCount(1);
});

it('never escapes the root even given a traversal attempt', function () {
    $manager = mediaManager();

    $manager->createFolder('../../../etc', 'evil');

    Storage::disk('public')->assertExists('mediacontents/etc/evil');
    Storage::disk('public')->assertMissing('etc/evil');
});

it('rejects a folder/file name containing a slash', function () {
    $manager = mediaManager();

    expect($manager->createFolder('', 'a/b'))->toBeFalse();

    Storage::disk('public')->put('mediacontents/logo.png', 'a');
    expect($manager->renameFile('', 'logo.png', 'a/b.png'))->toBeFalse();
});

it('builds breadcrumbs from the root down to the current sub-path', function () {
    $crumbs = mediaManager()->breadcrumbs('brand/logos');

    expect($crumbs)->toBe([
        ['label' => 'mediacontents', 'path' => ''],
        ['label' => 'brand', 'path' => 'brand'],
        ['label' => 'logos', 'path' => 'brand/logos'],
    ]);
});
