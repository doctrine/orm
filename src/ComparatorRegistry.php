<?php

namespace Doctrine\ORM;

class ComparatorRegistry
{
    /** @param array<class-string, callable> */
    private static array $callbacks = [];

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param callable(T, object): ?int
     */
    public static function register(string $class, callable $callback): void
    {
        self::$callbacks[$class] = $callback;
    }

    public static function reset(): void
    {
        self::$callbacks = [];
    }

    public static function compare(object $a, object $b): ?int
    {
        foreach (self::$callbacks as $class => $callback) {
            if (is_a($a, $class, false)) {
                $result = $callback($a, $b);

                if ($result !== null) {
                    return $result;
                }
            }
            if (is_a($b, $class, false)) {
                $result = $callback($b, $a);

                if ($result !== null) {
                    return -$result;
                }
            }
        }

        return null;
    }
}
