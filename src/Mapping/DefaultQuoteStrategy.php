<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Internal\SQLResultCasing;

use function array_map;
use function array_merge;
use function assert;
use function is_numeric;
use function preg_replace;
use function substr;

/**
 * A set of rules for determining the physical column, alias and table quotes
 */
class DefaultQuoteStrategy implements QuoteStrategy
{
    use SQLResultCasing;

    public function getColumnName(string $fieldName, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return isset($class->fieldMappings[$fieldName]->quoted)
            ? $platform->quoteSingleIdentifier($class->fieldMappings[$fieldName]->columnName)
            : $class->fieldMappings[$fieldName]->columnName;
    }

    /**
     * {@inheritDoc}
     *
     * @todo Table names should be computed in DBAL depending on the platform
     */
    public function getTableName(ClassMetadata $class, AbstractPlatform $platform): string
    {
        $tableName = $class->table['name'];

        if (! empty($class->table['schema'])) {
            $tableName = $class->table['schema'] . '.' . $class->table['name'];
        }

        return isset($class->table['quoted'])
            ? $platform->quoteSingleIdentifier($tableName)
            : $tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSequenceName(array $definition, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return isset($definition['quoted'])
            ? $platform->quoteSingleIdentifier($definition['sequenceName'])
            : $definition['sequenceName'];
    }

    public function getJoinColumnName(JoinColumnMapping $joinColumn, ClassMetadata $class, AbstractPlatform $platform): string
    {
        return isset($joinColumn->quoted)
            ? $platform->quoteSingleIdentifier($joinColumn->name)
            : $joinColumn->name;
    }

    public function getReferencedJoinColumnName(
        JoinColumnMapping $joinColumn,
        ClassMetadata $class,
        AbstractPlatform $platform,
    ): string {
        return isset($joinColumn->quoted)
            ? $platform->quoteSingleIdentifier($joinColumn->referencedColumnName)
            : $joinColumn->referencedColumnName;
    }

    public function getJoinTableName(
        ManyToManyOwningSideMapping $association,
        ClassMetadata $class,
        AbstractPlatform $platform,
    ): string {
        $schema = '';

        if (isset($association->joinTable->schema)) {
            $schema = $association->joinTable->schema . '.';
        }

        $tableName = $association->joinTable->name;

        if (isset($association->joinTable->quoted)) {
            $tableName = $platform->quoteSingleIdentifier($tableName);
        }

        return $schema . $tableName;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifierColumnNames(ClassMetadata $class, AbstractPlatform $platform): array
    {
        $quotedColumnNames = [];

        foreach ($class->identifier as $fieldName) {
            if (isset($class->fieldMappings[$fieldName])) {
                $quotedColumnNames[] = $this->getColumnName($fieldName, $class, $platform);

                continue;
            }

            // Association defined as Id field
            $assoc = $class->associationMappings[$fieldName];
            assert($assoc->isToOneOwningSide());
            $joinColumns            = $assoc->joinColumns;
            $assocQuotedColumnNames = array_map(
                static fn (JoinColumnMapping $joinColumn) => isset($joinColumn->quoted)
                    ? $platform->quoteSingleIdentifier($joinColumn->name)
                    : $joinColumn->name,
                $joinColumns,
            );

            $quotedColumnNames = array_merge($quotedColumnNames, $assocQuotedColumnNames);
        }

        return $quotedColumnNames;
    }

    public function getColumnAlias(
        string $columnName,
        int $counter,
        AbstractPlatform $platform,
        ClassMetadata|null $class = null,
    ): string {
        // 1 ) Concatenate column name and counter
        // 2 ) Trim the column alias to the maximum identifier length of the platform.
        //     If the alias is to long, characters are cut off from the beginning.
        // 3 ) Strip non alphanumeric characters
        // 4 ) Prefix with "_" if the result its numeric
        $columnName .= '_' . $counter;
        $columnName  = substr($columnName, -$platform->getMaxIdentifierLength());
        $columnName  = preg_replace('/[^A-Za-z0-9_]/', '', $columnName);
        $columnName  = is_numeric($columnName) ? '_' . $columnName : $columnName;

        return $this->getSQLResultCasing($platform, $columnName);
    }
}
