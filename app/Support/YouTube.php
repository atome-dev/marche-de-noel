<?php

namespace App\Support;

class YouTube
{
    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com', 'youtu.be'];

    /**
     * Privacy-friendly embed address of a YouTube link, or null for any other link.
     */
    public static function embedUrl(?string $url): ?string
    {
        $id = self::videoId($url);

        return $id ? 'https://www.youtube-nocookie.com/embed/'.$id : null;
    }

    public static function videoId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url(trim($url));
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $path = trim($parts['path'] ?? '', '/');
        parse_str($parts['query'] ?? '', $query);

        $id = match (true) {
            $host === 'youtu.be' => $path,
            $path === 'watch' => is_string($query['v'] ?? null) ? $query['v'] : '',
            (bool) preg_match('#^(?:embed|shorts|live|v)/([^/]+)#', $path, $matches) => $matches[1],
            default => '',
        };

        return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id) ? $id : null;
    }
}
