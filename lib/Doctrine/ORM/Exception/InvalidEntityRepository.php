<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\EntityRepository;
use LogicException;

final class InvalidEntityRepository extends LogicException implements ConfigurationException
{
    public static function fromClassName(string $className): self
    {
        return new self(
            "Invalid repository class '" . $className . "'. It must be a " . EntityRepository::class . '.',
        );
    }
}
