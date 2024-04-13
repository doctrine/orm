<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use function sprintf;

final class NamedNativeQueryNotFound extends ORMException implements ConfigurationException
{
    public static function fromName(string $name): self
    {
        return new self(sprintf(
            'Could not find a named native query by the name "%s"',
            $name
        ));
    }
}
