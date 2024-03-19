<?php declare(strict_types=1);

namespace MyApp\common;

final class Parallel
{
    private static function _runSubprocess(callable $func, $parameter): void
    {
        $pid = pcntl_fork();

        if ($pid == -1) {
            die("Failed to create process, parameter: {$parameter}");
        }
        // parent process
        elseif ($pid) {
        }
        // child process
        else {
            call_user_func($func, $parameter);
            exit(0);
        }
        return;
    }

    public static function doWorks(callable $func, array $parameters): array {
        $timeout = 60; // unit: sec

        $result = [];
        $timeStart = microtime(true);
        foreach ($parameters as $parameter) {
            self::_runSubprocess($func, $parameter);
        }
        // wait (join)
        while (count($result) < count($parameters)) {
            pcntl_wait($status);
            $result[] = "result: $status";
            if ((microtime(true) - $timeStart) / 1000 > $timeout) {
                throw new \Exception("Parallel process timed out.");
            }
        }
        return $result;
    }
}
