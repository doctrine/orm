<?php

declare(strict_types=1);

namespace Doctrine\ORM\Configuration;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\ConfigurationException;

final class UnknownEntityNamespace extends \Exception implements ConfigurationException
{
    public static function fromNamespaceAlias(string $entityNamespaceAlias) : self
    {
        return new self(
            "Unknown Entity namespace alias '$entityNamespaceAlias'."
        );
    }
}
