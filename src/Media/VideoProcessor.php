<?php

namespace OursBlanc\Xms\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * No transcoding in v1. This interface exists so poster generation (and,
 * later, transcoding) can be swapped in without touching call sites.
 */
interface VideoProcessor
{
    /**
     * Generate a poster image for an uploaded video, or return null if it
     * can't (e.g. the required binary isn't available).
     */
    public function generatePoster(Media $video): ?Media;
}
