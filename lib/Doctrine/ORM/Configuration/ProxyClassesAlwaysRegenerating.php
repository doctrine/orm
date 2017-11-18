<?php

declare(strict_types=1);

namespace Doctrine\ORM\Configuration;

use Doctrine\ORM\ConfigurationException;

final class ProxyClassesAlwaysRegenerating extends \Exception implements ConfigurationException
{
    public static function create() : self
    {
        return new self('Proxy Classes are always regenerating.');
    }
}
