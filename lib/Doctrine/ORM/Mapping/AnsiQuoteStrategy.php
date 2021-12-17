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
     * {@inheritdoc}
     */
    public function getColumnName($fieldName, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $class->fieldMappings[$fieldName]['columnName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform)
    {
        return $class->table['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $definition['sequenceName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $joinColumn['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $joinColumn['referencedColumnName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinTableName(array $association, ClassMetadata $class, AbstractPlatform $platform)
    {
        return $association['joinTable']['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform)
    {
        return $class->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnAlias($columnName, $counter, AbstractPlatform $platform, ?ClassMetadata $class = null)
    {
        return $this->getSQLResultCasing($platform, $columnName . '_' . $counter);
    }
}
