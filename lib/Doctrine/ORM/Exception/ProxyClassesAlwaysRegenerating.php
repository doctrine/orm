<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

final class ProxyClassesAlwaysRegenerating extends ORMException implements ConfigurationException
{
    public static function create(): self
    {
        return new self('Proxy Classes are always regenerating.');
    }
}
