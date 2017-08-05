<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * The default DefaultEntityListener
 *
 * @since   2.4
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DefaultEntityListenerResolver implements EntityListenerResolver
{
    /**
     * @var array Map to store entity listener instances.
     */
    private $instances = [];

    /**
     * {@inheritdoc}
     */
    public function clear($className = null)
    {
        if ($className === null) {
            $this->instances = [];

            return;
        }

        if (isset($this->instances[$className = trim($className, '\\')])) {
            unset($this->instances[$className]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register($object)
    {
        if ( ! is_object($object)) {
            throw new \InvalidArgumentException(sprintf('An object was expected, but got "%s".', gettype($object)));
        }

        $this->instances[get_class($object)] = $object;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($className)
    {
        if (isset($this->instances[$className = trim($className, '\\')])) {
           return $this->instances[$className];
        }

        return $this->instances[$className] = new $className();
    }
}
