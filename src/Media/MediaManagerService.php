<?php

namespace OursBlanc\Xms\Media;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use OursBlanc\Xms\Support\MediaManagerPath;

/**
 * Filesystem operations for the admin's Media Manager page, scoped to a
 * single fixed root directory (config('xms.media_manager.root')) on
 * config('xms.media_manager.disk') — every method takes a path relative to
 * that root, sanitized via MediaManagerPath so callers can never escape it.
 */
class MediaManagerService
{
    public function disk(): Filesystem
    {
        return Storage::disk(config('xms.media_manager.disk'));
    }

    public function root(): string
    {
        return MediaManagerPath::sanitize((string) config('xms.media_manager.root', 'mediacontents'));
    }

    /**
     * The disk-relative path for a given sub-path under the root, creating
     * the root itself on first use.
     */
    public function fullPath(string $subPath = ''): string
    {
        $full = MediaManagerPath::join($this->root(), MediaManagerPath::sanitize($subPath));

        if (! $this->disk()->exists($this->root())) {
            $this->disk()->makeDirectory($this->root());
        }

        return $full;
    }

    /**
     * @return array<int, string> folder names (not full paths), sorted
     */
    public function folders(string $subPath = ''): array
    {
        $full = $this->fullPath($subPath);

        if (! $this->disk()->exists($full)) {
            return [];
        }

        return collect($this->disk()->directories($full))
            ->map(fn (string $dir) => basename($dir))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, size: int, url: string, last_modified: int}>
     */
    public function files(string $subPath = ''): array
    {
        $full = $this->fullPath($subPath);
        $disk = $this->disk();

        if (! $disk->exists($full)) {
            return [];
        }

        return collect($disk->files($full))
            ->map(fn (string $file) => [
                'name' => basename($file),
                'size' => $disk->size($file),
                'url' => $disk->url($file),
                'last_modified' => $disk->lastModified($file),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    public function createFolder(string $subPath, string $name): bool
    {
        if (! MediaManagerPath::isValidName($name)) {
            return false;
        }

        $target = MediaManagerPath::join($this->fullPath($subPath), $name);

        if ($this->disk()->exists($target)) {
            return false;
        }

        return $this->disk()->makeDirectory($target);
    }

    public function deleteFolder(string $subPath, string $name): bool
    {
        if (! MediaManagerPath::isValidName($name)) {
            return false;
        }

        return $this->disk()->deleteDirectory(MediaManagerPath::join($this->fullPath($subPath), $name));
    }

    public function renameFolder(string $subPath, string $oldName, string $newName): bool
    {
        if (! MediaManagerPath::isValidName($oldName) || ! MediaManagerPath::isValidName($newName)) {
            return false;
        }

        $base = $this->fullPath($subPath);
        $from = MediaManagerPath::join($base, $oldName);
        $to = MediaManagerPath::join($base, $newName);

        if (! $this->disk()->exists($from) || $this->disk()->exists($to)) {
            return false;
        }

        return $this->disk()->move($from, $to);
    }

    public function deleteFile(string $subPath, string $name): bool
    {
        if (! MediaManagerPath::isValidName($name)) {
            return false;
        }

        return $this->disk()->delete(MediaManagerPath::join($this->fullPath($subPath), $name));
    }

    public function renameFile(string $subPath, string $oldName, string $newName): bool
    {
        if (! MediaManagerPath::isValidName($oldName) || ! MediaManagerPath::isValidName($newName)) {
            return false;
        }

        $base = $this->fullPath($subPath);
        $from = MediaManagerPath::join($base, $oldName);
        $to = MediaManagerPath::join($base, $newName);

        if (! $this->disk()->exists($from) || $this->disk()->exists($to)) {
            return false;
        }

        return $this->disk()->move($from, $to);
    }

    /**
     * Breadcrumb trail for a sub-path, from the root down to the current
     * directory — each entry's `path` is what to pass back to browse there.
     *
     * @return array<int, array{label: string, path: string}>
     */
    public function breadcrumbs(string $subPath): array
    {
        $crumbs = [['label' => $this->root(), 'path' => '']];
        $accumulated = [];

        foreach (explode('/', MediaManagerPath::sanitize($subPath)) as $segment) {
            if ($segment === '') {
                continue;
            }

            $accumulated[] = $segment;
            $crumbs[] = ['label' => $segment, 'path' => implode('/', $accumulated)];
        }

        return $crumbs;
    }
}
