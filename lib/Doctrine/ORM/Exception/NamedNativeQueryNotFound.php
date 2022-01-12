<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use LogicException;

use function sprintf;

final class NamedNativeQueryNotFound extends LogicException implements ConfigurationException
{
    public static function fromName(string $name): self
    {
        return new self(sprintf(
            'Could not find a named native query by the name "%s"',
            $name
        ));
    }
}
