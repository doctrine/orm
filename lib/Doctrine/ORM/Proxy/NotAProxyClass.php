<?php

declare(strict_types=1);

namespace Doctrine\ORM\Proxy;

use Doctrine\ORM\Exception\ORMException;
use InvalidArgumentException;

use function sprintf;

final class NotAProxyClass extends InvalidArgumentException implements ORMException
{
    public function __construct(string $className, string $proxyNamespace)
    {
        parent::__construct(sprintf(
            'The class "%s" is not part of the proxy namespace "%s"',
            $className,
            $proxyNamespace,
        ));
    }
}
