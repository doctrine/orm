<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Internal\SQLResultCasing;

/**
 * ANSI compliant quote strategy, this strategy does not apply any quote.
 * To use this strategy all mapped tables and columns should be ANSI compliant.
 */
class AnsiQuoteStrategy implements QuoteStrategy
{
    use SQLResultCasing;

    /**
     * {@inheritDoc}
     */
    public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $class->fieldMappings[$fieldName]['columnName'];
    }

    /**
     * {@inheritDoc}
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform)
    {
        return $class->table['name'];
    }

    /**
     * {@inheritDoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $definition['sequenceName'];
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $joinColumn['name'];
    }

    /**
     * {@inheritDoc}
     */
    public function getReferencedJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $joinColumn['referencedColumnName'];
    }

    /**
     * {@inheritDoc}
     */
    public function getJoinTableName(array $association, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $association['joinTable']['name'];
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform)
    {
        return $class->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ?ClassMetadata $class = null)
    {
        return $this->getSQLResultCasing($platform, $columnName . '_' . $counter);
    }
}
