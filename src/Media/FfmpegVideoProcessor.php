<?php

namespace OursBlanc\Xms\Media;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class FfmpegVideoProcessor implements VideoProcessor
{
    public function generatePoster(Media $video): ?Media
    {
        if (! $this->ffmpegAvailable()) {
            Log::info('xms: ffmpeg binary not found, skipping poster generation.', ['media_id' => $video->id]);

            return null;
        }

        $posterPath = tempnam(sys_get_temp_dir(), 'xms-poster-').'.jpg';

        $result = Process::run([
            'ffmpeg', '-y',
            '-ss', '00:00:00',
            '-i', $video->getPath(),
            '-frames:v', '1',
            '-update', '1',
            '-pix_fmt', 'yuvj420p',
            $posterPath,
        ]);

        if (! $result->successful() || ! is_file($posterPath) || filesize($posterPath) === 0) {
            Log::warning('xms: ffmpeg poster generation failed.', [
                'media_id' => $video->id,
                'error' => $result->errorOutput(),
            ]);

            @unlink($posterPath);

            return null;
        }

        $poster = $video->model
            ->addMedia($posterPath)
            ->usingFileName(pathinfo((string) $video->file_name, PATHINFO_FILENAME).'-poster.jpg')
            ->withCustomProperties(['is_poster_for' => $video->id])
            ->toMediaCollection($video->collection_name);

        @unlink($posterPath);

        return $poster;
    }

    protected function ffmpegAvailable(): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }

        return trim((string) @shell_exec('command -v ffmpeg')) !== '';
    }
}
