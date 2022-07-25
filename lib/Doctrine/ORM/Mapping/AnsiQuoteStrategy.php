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

    public function getColumnName(
        string $fieldName,
        ClassMetadata $class,
        AbstractPlatform $platform,
    ): string {
        return $class->fieldMappings[$fieldName]['columnName'];
    }

    public function getTableName(ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $class->table['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $definition['sequenceName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinColumnName(array $joinColumn, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $joinColumn['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedJoinColumnName(
        array $joinColumn,
        ClassMetadata $class,
        AbstractPlatform $platform,
    ): string {
        return $joinColumn['referencedColumnName'];
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinTableName(array $association, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return $association['joinTable']['name'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform): array
    {
        return $class->identifier;
    }

    public function getColumnAlias(
        string $columnName,
        int $counter,
        AbstractPlatform $platform,
        ClassMetadata|null $class = null,
    ): string {
        return $this->getSQLResultCasing($platform, $columnName . '_' . $counter);
    }
}
