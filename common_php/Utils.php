<?php declare(strict_types=1);

namespace MyApp\common;

use Psr\Http\Message\ServerRequestInterface;
use CloudEvents\V1\CloudEventInterface;

final class Utils
{
    public function __construct()
    {
    }

    public static function getConfig(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents == false) {
            throw new \Exception("Could not read config file: {$path}");
        }
        $result = json_decode($contents, true);
        if (is_null($result)) {
            throw new \Exception("Failed to parse config file: {$path}");
        }
        return $result;
    }

    public static function isLocalHttp(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeader("Host")[0], "localhost") || str_contains($request->getHeader("Host")[0], "127.0.0.1");
    }

    public static function isLocalEvent(CloudEventInterface $event): bool
    {
        return ($event->getId() === "9999999999");
    }

    public static function getBasePath(bool $isLocal, string $appName): string
    {
        if ($isLocal) {
            return "/";
        }

        return "/{$appName}/";
    }

    public static function getBaseUrl(bool $isLocal, string $appName): string
    {
        if ($isLocal) {
            return "http://localhost:8080" . self::getBasePath($isLocal, $appName);
        }
        else {
            $config = self::getConfig(__DIR__ . "/configs/common.json");
            return $config["base_url"] . self::getBasePath($isLocal, $appName);
        }
    }

    public static function invokePrivateMethod(Object $object, string $methodName, ...$params)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        $result = $method->invoke($object, ...$params);
        return $result;
    }

    public static function sortObjectArrayByProperty(array $ary, string $property): array
    {
        usort(
            $ary,
            function ($a, $b) use($property) {
                if ($a->$property == $b->$property) return 0;
                return ($a->$property < $b->$property) ? -1 : 1;
            }
        );
        return $ary;
    }
}
