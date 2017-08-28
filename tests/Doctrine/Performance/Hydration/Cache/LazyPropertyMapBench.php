<?php

namespace Doctrine\Performance\Hydration\Cache;

use Doctrine\ORM\Internal\Hydration\Cache\LazyPropertyMap;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use stdClass;

/**
 * @BeforeMethods({"init"})
 */
final class LazyPropertyMapBench
{
    /**
     * @var callable
     */
    private $initializer;

    /**
     * LazyPropertyMap
     */
    private $lazyMap;

    /**
     * @var array
     */
    private $array;

    /**
     * @var int
     */
    private $currentKey = 0;

    public function init() : void
    {
        $this->initializer = function (string $name) : stdClass {
            return (object) [$name => true];
        };
        $this->lazyMap     = new LazyPropertyMap($this->initializer);
        $this->array       = [
            'initialized' => ($this->initializer)('initialized'),
        ];

        $this->lazyMap->initialized;
    }

    public function benchInitializedPropertyAccess() : stdClass
    {
        $key = 'initialized';

        return $this->lazyMap->$key;
    }

    public function benchInitializedArrayKeyAccess() : stdClass
    {
        $key = 'initialized';

        return $this->array[$key] ?? $this->array[$key] = ($this->initializer)($key);
    }

    public function benchUninitializedPropertyAccess() : stdClass
    {
        $key = 'key' . $this->currentKey++;

        return $this->lazyMap->$key;
    }

    public function benchUninitializedArrayAccess() : stdClass
    {
        $key = 'key' . $this->currentKey++;

        return $this->array[$key] ?? $this->array[$key] = ($this->initializer)($key);
    }
}
