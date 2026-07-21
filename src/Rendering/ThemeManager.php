<?php

namespace OursBlanc\Xms\Rendering;

class ThemeManager
{
    public const VIEW_NAMESPACE = 'theme';

    public function active(): ?string
    {
        return config('xms.theme');
    }

    public function viewsPath(): ?string
    {
        $theme = $this->active();

        if (! $theme) {
            return null;
        }

        $path = resource_path("themes/{$theme}/views");

        return is_dir($path) ? $path : null;
    }

    public function hasViewNamespace(): bool
    {
        return $this->viewsPath() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        $theme = $this->active();

        if (! $theme) {
            return [];
        }

        $path = resource_path("themes/{$theme}/theme.json");

        if (! is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    /**
     * @return array<int, string> Vite entry points to build for this theme.
     */
    public function assetEntries(): array
    {
        return $this->manifest()['assets'] ?? [];
    }
}
