<?php

namespace OursBlanc\Xms\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Thin wrapper around the Pexels API used by the admin's "Search Pexels"
 * picker on image/video fields. searchPreview() only ever touches URLs
 * already hosted by Pexels (thumbnails), so browsing/paginating never
 * downloads or stores anything; only resolveAndStorePhoto()/
 * resolveAndStoreVideo() (called once a specific result is picked) download
 * the full-size file, storing it under the same "pending" disk path a
 * manual PageMediaUpload upload would produce, so PageMediaSynchronizer
 * picks it up identically once the page is saved.
 */
class PexelsService
{
    protected const PHOTOS_BASE = 'https://api.pexels.com/v1';

    protected const VIDEOS_BASE = 'https://api.pexels.com/videos';

    public function enabled(): bool
    {
        return (bool) config('xms.pexels.api_key');
    }

    /**
     * @return array{photos: array<int, array<string, mixed>>}
     */
    public function searchPhotos(string $query, int $page = 1): array
    {
        $endpoint = trim($query) === '' ? self::PHOTOS_BASE.'/curated' : self::PHOTOS_BASE.'/search';

        $response = $this->request($endpoint, array_filter([
            'query' => trim($query) !== '' ? $query : null,
            'page' => max(1, $page),
            'per_page' => 15,
        ]));

        $photos = collect($response['photos'] ?? [])->map(fn (array $photo) => [
            'id' => $photo['id'],
            'thumbnail' => $photo['src']['medium'],
            'width' => $photo['width'],
            'height' => $photo['height'],
            'photographer' => $photo['photographer'],
            'url' => $photo['url'],
        ])->all();

        return ['photos' => $photos];
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>}
     */
    public function searchVideos(string $query, int $page = 1): array
    {
        $endpoint = trim($query) === '' ? self::VIDEOS_BASE.'/popular' : self::VIDEOS_BASE.'/search';

        $response = $this->request($endpoint, array_filter([
            'query' => trim($query) !== '' ? $query : null,
            'page' => max(1, $page),
            'per_page' => 15,
        ]));

        $videos = collect($response['videos'] ?? [])->map(fn (array $video) => [
            'id' => $video['id'],
            'thumbnail' => $video['image'],
            'duration' => $video['duration'],
            'user' => $video['user']['name'] ?? null,
            'url' => $video['url'],
            // A small-quality file straight from the search response, so each
            // grid tile can preview-play in place without any extra request.
            'preview_url' => $this->smallestSuitableVideoFile($video['video_files'] ?? [])['link'] ?? null,
        ])->all();

        return ['videos' => $videos];
    }

    /**
     * @return ?array{path: string, alt: string, attribution: string, attribution_url: string}
     */
    public function resolveAndStorePhoto(int $photoId): ?array
    {
        if ($photoId <= 0) {
            return null;
        }

        $photo = $this->request(self::PHOTOS_BASE."/photos/{$photoId}");

        if (! $photo) {
            return null;
        }

        $sourceUrl = $photo['src']['original'] ?? $photo['src']['large2x'] ?? null;

        if (! $sourceUrl) {
            return null;
        }

        $path = $this->downloadToPending($sourceUrl, config('xms.pexels.photo_max_bytes'), 'jpg');

        if (! $path) {
            return null;
        }

        return [
            'path' => $path,
            'alt' => $photo['alt'] ?: "Photo by {$photo['photographer']} on Pexels",
            'attribution' => "Photo by {$photo['photographer']} on Pexels",
            'attribution_url' => $photo['url'],
        ];
    }

    /**
     * @return ?array{path: string, attribution: string, attribution_url: string}
     */
    public function resolveAndStoreVideo(int $videoId): ?array
    {
        if ($videoId <= 0) {
            return null;
        }

        $video = $this->request(self::VIDEOS_BASE."/videos/{$videoId}");

        if (! $video) {
            return null;
        }

        $file = $this->smallestSuitableVideoFile($video['video_files'] ?? []);

        if (! $file) {
            return null;
        }

        $path = $this->downloadToPending($file['link'], config('xms.pexels.video_max_bytes'), 'mp4');

        if (! $path) {
            return null;
        }

        $author = $video['user']['name'] ?? 'a Pexels contributor';

        return [
            'path' => $path,
            'attribution' => "Video by {$author} on Pexels",
            'attribution_url' => $video['url'],
        ];
    }

    /**
     * Picks the smallest file that's still at least 640px wide, so the
     * download stays reasonable; falls back to the smallest file overall if
     * none reach that width.
     *
     * @param  array<int, array<string, mixed>>  $files
     * @return ?array<string, mixed>
     */
    protected function smallestSuitableVideoFile(array $files): ?array
    {
        $files = collect($files)->filter(fn (array $f) => ($f['file_type'] ?? null) === 'video/mp4');

        if ($files->isEmpty()) {
            return null;
        }

        $suitable = $files->filter(fn (array $f) => ($f['width'] ?? 0) >= 640)
            ->sortBy('width')
            ->first();

        return $suitable ?? $files->sortBy('width')->first();
    }

    protected function downloadToPending(string $url, int $maxBytes, string $extension): ?string
    {
        $response = Http::withOptions(['allow_redirects' => false])->timeout(20)->get($url);

        if (! $response->successful() || strlen($response->body()) > $maxBytes) {
            return null;
        }

        $disk = config('xms.media_disk');
        $path = 'xms-pending/'.Str::uuid().'.'.$extension;

        Storage::disk($disk)->put($path, $response->body());

        return $path;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function request(string $url, array $query = []): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $response = Http::withHeaders(['Authorization' => config('xms.pexels.api_key')])
            ->timeout(10)
            ->get($url, $query);

        return $response->successful() ? $response->json() : [];
    }
}
