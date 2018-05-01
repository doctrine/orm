<?php

declare(strict_types=1);

namespace Doctrine\ORM\Configuration\Exception;

final class ProxyClassesAlwaysRegenerating extends \LogicException implements ConfigurationException
{
    public static function new() : self
    {
        return new self('Proxy Classes are always regenerating.');
    }
}
