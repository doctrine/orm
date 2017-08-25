<?php
declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration\Cache;

/**
 * Concept taken from ocramius/lazy-map
 *
 * @link https://github.com/Ocramius/LazyMap/blob/1.0.0/src/LazyMap/CallbackLazyMap.php
 */
final class LazyPropertyMap
{
    public function __construct(callable $instantiate)
    {
        $this->{__CLASS__ . "\0callback"} = $instantiate;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        $this->$name = ($this->{__CLASS__ . "\0callback"})($name);

        return $this->$name;
    }
}
