<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use function sprintf;

final class NamedQueryNotFound extends ORMException implements ConfigurationException
{
    public static function fromName(string $name): self
    {
        return new self(sprintf(
            'Could not find a named query by the name "%s"',
            $name
        ));
    }
}
