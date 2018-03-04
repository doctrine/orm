<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\Exception\ConfigurationException;

final class UnknownEntityNamespace extends \Exception implements ConfigurationException
{
    public static function fromNamespaceAlias(string $entityNamespaceAlias) : self
    {
        return new self(
            "Unknown Entity namespace alias '$entityNamespaceAlias'."
        );
    }
}
