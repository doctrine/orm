<?php

declare(strict_types=1);

namespace Doctrine\ORM\Reflection;

use Doctrine\ORM\Proxy\Proxy;

/**
 * PHP Runtime Reflection Public Property - special overrides for public properties.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @since  2.4
 */
class RuntimePublicReflectionProperty extends \ReflectionProperty
{
    /**
     * {@inheritDoc}
     *
     * Checks is the value actually exist before fetching it.
     * This is to avoid calling `__get` on the provided $object if it
     * is a {@see \Doctrine\ORM\Proxy\Proxy}.
     */
    public function getValue($object = null)
    {
        $name = $this->getName();

        if ($object instanceof Proxy && ! $object->__isInitialized()) {
            $originalInitialized = $object->__isInitialized();

            $object->__setInitialized(true);
            $val = isset($object->$name) ? $object->$name : null;
            $object->__setInitialized($originalInitialized);

            return $val;
        }

        return isset($object->$name) ? parent::getValue($object) : null;
    }

    /**
     * {@inheritDoc}
     *
     * Avoids triggering lazy loading via `__set` if the provided object
     * is a {@see \Doctrine\ORM\Proxy\Proxy}.
     * @link https://bugs.php.net/bug.php?id=63463
     */
    public function setValue($object, $value = null)
    {
        if ($object instanceof Proxy && ! $object->__isInitialized()) {
            $originalInitialized = $object->__isInitialized();

            $object->__setInitialized(true);
            parent::setValue($object, $value);
            $object->__setInitialized($originalInitialized);

            return;
        }

        parent::setValue($object, $value);
    }
}
