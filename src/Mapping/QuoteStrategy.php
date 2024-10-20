<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * A set of rules for determining the column, alias and table quotes.
 */
interface QuoteStrategy
{
    /**
     * Gets the (possibly quoted) column name for safe use in an SQL statement.
     */
    public function getColumnName(string $fieldName, ClassMetadata $class, AbstractPlatform $platform): string;

    /**
     * Gets the (possibly quoted) primary table name for safe use in an SQL statement.
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform): string;

    /**
     * Gets the (possibly quoted) sequence name for safe use in an SQL statement.
     *
     * @param mixed[] $definition
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform): string;

    /** Gets the (possibly quoted) name of the join table. */
    public function getJoinTableName(
        ManyToManyOwningSideMapping $association,
        ClassMetadata $class,
        AbstractPlatform $platform,
    ): string;

    /**
     * Gets the (possibly quoted) join column name.
     */
    public function getJoinColumnName(JoinColumnMapping $joinColumn, ClassMetadata $class, AbstractPlatform $platform): string;

    /**
     * Gets the (possibly quoted) join column name.
     */
    public function getReferencedJoinColumnName(
        JoinColumnMapping $joinColumn,
        ClassMetadata $class,
        AbstractPlatform $platform,
    ): string;

    /**
     * Gets the (possibly quoted) identifier column names for safe use in an SQL statement.
     *
     * @psalm-return list<string>
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform): array;

    /**
     * Gets the column alias.
     */
    public function getColumnAlias(
        string $columnName,
        int $counter,
        AbstractPlatform $platform,
        ClassMetadata|null $class = null,
    ): string;
}
