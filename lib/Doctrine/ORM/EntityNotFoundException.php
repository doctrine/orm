<?php

declare(strict_types=1);

namespace Doctrine\ORM;

use Doctrine\ORM\Exception\ORMException;
use RuntimeException;

use function implode;
use function sprintf;

/**
 * Exception thrown when a Proxy fails to retrieve an Entity result.
 */
class EntityNotFoundException extends RuntimeException implements ORMException
{
    /**
     * Static constructor.
     *
     * @param string[] $id
     */
    public static function fromClassNameAndIdentifier(string $className, array $id): self
    {
        $ids = [];

        foreach ($id as $key => $value) {
            $ids[] = $key . '(' . $value . ')';
        }

        return new self(
            'Entity of type \'' . $className . '\'' . ($ids ? ' for IDs ' . implode(', ', $ids) : '') . ' was not found',
        );
    }

    /**
     * Instance for which no identifier can be found
     */
    public static function noIdentifierFound(string $className): self
    {
        return new self(sprintf(
            'Unable to find "%s" entity identifier associated with the UnitOfWork',
            $className,
        ));
    }
}
