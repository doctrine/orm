<?php

declare(strict_types=1);

namespace Doctrine\ORM\Internal\Hydration;

class HydrationException extends \Doctrine\ORM\ORMException
{
    /**
     * @return HydrationException
     */
    public static function nonUniqueResult()
    {
        return new self("The result returned by the query was not unique.");
    }

    /**
     * @param string $alias
     * @param string $parentAlias
     *
     * @return HydrationException
     */
    public static function parentObjectOfRelationNotFound($alias, $parentAlias)
    {
        return new self("The parent object of entity result with alias '$alias' was not found."
            . " The parent alias is '$parentAlias'.");
    }

    /**
     * @param string $dqlAlias
     *
     * @return HydrationException
     */
    public static function emptyDiscriminatorValue($dqlAlias)
    {
        return new self("The DQL alias '" . $dqlAlias . "' contains an entity ".
            "of an inheritance hierarchy with an empty discriminator value. This means " .
            "that the database contains inconsistent data with an empty " .
            "discriminator value in a table row."
        );
    }

    /**
     * @since 2.3
     *
     * @param string $entityName
     * @param string $discrColumnName
     * @param string $dqlAlias
     *
     * @return HydrationException
     */
    public static function missingDiscriminatorColumn($entityName, $discrColumnName, $dqlAlias)
    {
        return new self(sprintf(
            'The discriminator column "%s" is missing for "%s" using the DQL alias "%s".',
            $discrColumnName, $entityName, $dqlAlias
        ));
    }

    /**
     * @since 2.3
     *
     * @param string $entityName
     * @param string $discrColumnName
     * @param string $dqlAlias
     *
     * @return HydrationException
     */
    public static function missingDiscriminatorMetaMappingColumn($entityName, $discrColumnName, $dqlAlias)
    {
        return new self(sprintf(
            'The meta mapping for the discriminator column "%s" is missing for "%s" using the DQL alias "%s".',
            $discrColumnName, $entityName, $dqlAlias
        ));
    }

    /**
     * @param string $discrValue
     * @param array  $discrMap
     *
     * @return HydrationException
     */
    public static function invalidDiscriminatorValue($discrValue, $discrMap)
    {
        return new self(sprintf(
            'The discriminator value "%s" is invalid. It must be one of "%s".',
            $discrValue, implode('", "', $discrMap)
        ));
    }
}
