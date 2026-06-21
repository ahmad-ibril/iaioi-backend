<?php

namespace App\Support;

class PublicStorageUrl
{
    public static function fromPath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return self::origin().'/storage/'.$path;
    }

    private static function origin(): string
    {
        $configured = self::rootOrigin((string) config('app.url', ''));

        if (self::isPublicOrigin($configured)) {
            return $configured;
        }

        if (app()->bound('request')) {
            $requestOrigin = self::rootOrigin(request()->getSchemeAndHttpHost());

            if (self::isPublicOrigin($requestOrigin)) {
                if (str_contains($requestOrigin, 'iaioi.com')) {
                    return preg_replace('/^http:/', 'https:', $requestOrigin)
                        ?? $requestOrigin;
                }

                return $requestOrigin;
            }
        }

        return 'https://iaioi.com';
    }

    private static function isPublicOrigin(string $origin): bool
    {
        return $origin !== ''
            && ! str_contains($origin, 'localhost')
            && ! str_contains($origin, '127.0.0.1');
    }

    private static function rootOrigin(string $url): string
    {
        $url = rtrim($url, '/');
        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return $url;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port;
    }
}
