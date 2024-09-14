<?php

declare(strict_types=1);

namespace Doctrine\ORM\Proxy;

use Doctrine\Persistence\Proxy;

/**
 * @internal
 *
 * @template T of object
 * @template-extends Proxy<T>
 *
 * @method void __setInitialized(bool $initialized)
 */
interface InternalProxy extends Proxy
{
}
