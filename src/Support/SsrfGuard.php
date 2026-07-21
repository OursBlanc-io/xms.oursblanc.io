<?php

namespace OursBlanc\Xms\Support;

use InvalidArgumentException;

/**
 * Baseline SSRF protection for `attach_media_from_url`: only http/https,
 * only hosts that resolve to public, non-reserved IP addresses. The HTTP
 * client call itself must additionally disable redirect-following, since a
 * redirect could otherwise be used to reach an internal address after this
 * check has passed.
 */
class SsrfGuard
{
    public static function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);

        if (! $parts || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http/https URLs are allowed.');
        }

        $host = $parts['host'] ?? '';

        if ($host === '') {
            throw new InvalidArgumentException('The URL has no host.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);

        if ($ips === []) {
            throw new InvalidArgumentException("Could not resolve host [{$host}].");
        }

        foreach ($ips as $ip) {
            if (! static::isPublicIp($ip)) {
                throw new InvalidArgumentException("The URL resolves to a disallowed private/internal address ({$ip}).");
            }
        }
    }

    protected static function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
