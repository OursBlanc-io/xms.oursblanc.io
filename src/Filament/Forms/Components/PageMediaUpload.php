<?php

namespace OursBlanc\Xms\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;

/**
 * A thin FileUpload wrapper used for every media field across generic and
 * custom blocks. It intentionally does NOT attach to the media library
 * itself: on submit it just stores the upload under a pending directory on
 * the configured disk and dehydrates to that path (a string). The page not
 * having an id yet on create (and the current block's uuid living in a
 * sibling hidden field inside a Builder item, not reachable in a stable way
 * from here) makes attaching here fragile; OursBlanc\Xms\Media\PageMediaSynchronizer
 * instead turns every pending path into a real Media model — keyed by the
 * block's own uuid — once the Page has been persisted, from an
 * afterCreate()/afterSave() Filament hook.
 */
class PageMediaUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->disk(fn () => config('xms.media_disk'));
        $this->directory('xms-pending');
        $this->visibility('public');
    }
}
