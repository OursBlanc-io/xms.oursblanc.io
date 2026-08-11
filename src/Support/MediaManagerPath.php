<?php

namespace OursBlanc\Xms\Support;

class MediaManagerPath
{
    /**
     * Strips `.`/`..` segments, empty segments, and leading/trailing
     * slashes, so a browsed sub-path can never escape the media manager's
     * root directory (e.g. via "../../etc" or a leading "/").
     */
    public static function sanitize(string $path): string
    {
        $segments = array_filter(
            explode('/', str_replace('\\', '/', $path)),
            fn (string $segment) => $segment !== '' && $segment !== '.' && $segment !== '..',
        );

        return implode('/', $segments);
    }

    /**
     * A single path segment (a file or folder name): like sanitize(), but
     * additionally rejects the value outright (rather than silently
     * stripping it) if it contains a slash, since a name is never meant to
     * introduce new path segments.
     */
    public static function isValidName(string $name): bool
    {
        return $name !== ''
            && $name !== '.'
            && $name !== '..'
            && ! str_contains($name, '/')
            && ! str_contains($name, '\\');
    }

    public static function join(string ...$segments): string
    {
        $parts = array_filter(array_map(
            fn (string $segment) => trim($segment, '/'),
            $segments,
        ), fn (string $segment) => $segment !== '');

        return implode('/', $parts);
    }
}
