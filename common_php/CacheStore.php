<?php declare(strict_types=1);

namespace MyApp\common;

final class CacheStore
{
    public function __construct()
    {
    }

    private static function __rootPath(): string
    {
        return "myapp";
    }

    private static function __hasRootPathData(): bool
    {
        return isset($_SESSION[self::__rootPath()]);
    }

    public static function prune(): void
    {
        if (!self::__hasRootPathData()) {
            return;
        }
        foreach (array_keys($_SESSION[self::__rootPath()]) as $key) {
            self::clear($key);
        }
    }

    public static function clear(string $key): void
    {
        if (!self::__hasRootPathData()) {
            return;
        }
        unset($_SESSION[self::__rootPath()][$key]);
    }

    public static function get(string $key, mixed $nullValue=null): mixed
    {
        if (isset($_SESSION[self::__rootPath()][$key])) {
            return $_SESSION[self::__rootPath()][$key];
        }
        return $nullValue;
    }

    public static function put(string $key, mixed $variable): void
    {
        $_SESSION[self::__rootPath()][$key] = $variable;
    }
}