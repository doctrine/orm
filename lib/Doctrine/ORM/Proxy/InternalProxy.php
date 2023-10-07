<?php

declare(strict_types=1);

namespace Doctrine\ORM\Proxy;

use Doctrine\Persistence\Proxy;

/**
 * @internal
 *
 * @template T of object
 * @template-extends Proxy<T>
 */
interface InternalProxy extends Proxy
{
    public function __setInitialized(bool $initialized): void;
}
