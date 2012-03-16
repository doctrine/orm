<?php

namespace Doctrine\ORM\Internal\Hydration;

class HydrationException extends \Doctrine\ORM\ORMException
{
    public static function nonUniqueResult()
    {
        return new self("The result returned by the query was not unique.");
    }

    public static function parentObjectOfRelationNotFound($alias, $parentAlias)
    {
        return new self("The parent object of entity result with alias '$alias' was not found."
                . " The parent alias is '$parentAlias'.");
    }

    public static function emptyDiscriminatorValue($dqlAlias)
    {
        return new self("The DQL alias '" . $dqlAlias . "' contains an entity ".
            "of an inheritance hierachy with an empty discriminator value. This means " .
            "that the database contains inconsistent data with an empty " .
            "discriminator value in a table row."
        );
    }

    /**
     * @since 2.3
     * @param   string $entityName
     * @param   string $discrColumnName
     * @param   string $dqlAlias
     * @return  HydrationException
     */
    public static function missingDiscriminatorColumn($entityName, $discrColumnName, $dqlAlias)
    {
        return new self("The discriminator column '$discrColumnName' "
                . "is missing for '$entityName' using the DQL alias '$dqlAlias'.");
    }

    /**
     * @since 2.3
     * @param   string $entityName
     * @param   string $discrColumnName
     * @param   string $dqlAlias
     * @return  HydrationException
     */
    public static function missingDiscriminatorMetaMappingColumn($entityName, $discrColumnName, $dqlAlias)
    {
        return new self("The meta mapping for the discriminator column '$discrColumnName' "
                . "is missing for '$entityName' using the DQL alias '$dqlAlias'.");
    }
}