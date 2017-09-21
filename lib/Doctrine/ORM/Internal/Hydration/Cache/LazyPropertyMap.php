<?php
declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration\Cache;

/**
 * Concept taken from ocramius/lazy-map
 *
 * @link https://github.com/Ocramius/LazyMap/blob/1.0.0/src/LazyMap/CallbackLazyMap.php
 * @internal do not use: internal class only
 */
final class LazyPropertyMap
{
    public function __construct(callable $instantiate)
    {
        // Note: the reason why we use dynamic access to create a private-ish property is
        //       that we do not want this property to be statically defined. If that
        //       happens, then a consumer of `__get` may access it, and that's no good
        $this->{self::class . "\0callback"} = $instantiate;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        $this->$name = ($this->{self::class . "\0callback"})($name);

        return $this->$name;
    }
}
