<?php

declare(strict_types=1);

namespace Doctrine\ORM\Proxy;

use Doctrine\Common\Proxy\Proxy as BaseProxy;

/**
 * Interface for proxy classes.
 *
 * @deprecated 2.14. Use \Doctrine\Persistence\Proxy instead
 *
 * @template T of object
 * @template-extends BaseProxy<T>
 * @template-extends InternalProxy<T>
 */
interface Proxy extends BaseProxy, InternalProxy
{
}
