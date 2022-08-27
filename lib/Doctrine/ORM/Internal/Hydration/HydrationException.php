<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

use Doctrine\ORM\Exception\ORMException;
use Exception;

use function implode;
use function sprintf;

class HydrationException extends Exception implements ORMException
{
    public static function nonUniqueResult(): self
    {
        return new self('The result returned by the query was not unique.');
    }

    public static function parentObjectOfRelationNotFound(string $alias, string $parentAlias): self
    {
        return new self(sprintf(
            "The parent object of entity result with alias '%s' was not found."
            . " The parent alias is '%s'.",
            $alias,
            $parentAlias,
        ));
    }

    public static function emptyDiscriminatorValue(string $dqlAlias): self
    {
        return new self("The DQL alias '" . $dqlAlias . "' contains an entity " .
            'of an inheritance hierarchy with an empty discriminator value. This means ' .
            'that the database contains inconsistent data with an empty ' .
            'discriminator value in a table row.');
    }

    public static function missingDiscriminatorColumn(string $entityName, string $discrColumnName, string $dqlAlias): self
    {
        return new self(sprintf(
            'The discriminator column "%s" is missing for "%s" using the DQL alias "%s".',
            $discrColumnName,
            $entityName,
            $dqlAlias,
        ));
    }

    public static function missingDiscriminatorMetaMappingColumn(string $entityName, string $discrColumnName, string $dqlAlias): self
    {
        return new self(sprintf(
            'The meta mapping for the discriminator column "%s" is missing for "%s" using the DQL alias "%s".',
            $discrColumnName,
            $entityName,
            $dqlAlias,
        ));
    }

    /** @param list<int|string> $discrValues */
    public static function invalidDiscriminatorValue(string $discrValue, array $discrValues): self
    {
        return new self(sprintf(
            'The discriminator value "%s" is invalid. It must be one of "%s".',
            $discrValue,
            implode('", "', $discrValues),
        ));
    }
}
