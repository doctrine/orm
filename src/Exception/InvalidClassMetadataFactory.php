<?php

declare(strict_types=1);

namespace Doctrine\ORM\Exception;

use Doctrine\ORM\Mapping\ClassMetadataFactoryInterface;
use LogicException;

use function sprintf;

class InvalidClassMetadataFactory extends LogicException implements ConfigurationException
{
    public static function create(string $className): self
    {
        return new self(sprintf("Invalid class metadata factory class '%s'. It must be a %s.", $className, ClassMetadataFactoryInterface::class));
    }
}
