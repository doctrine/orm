<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

/**
 * A set of rules for determining the physical column and table names
 *
 * @link    www.doctrine-project.org
 */
interface NamingStrategy
{
    /**
     * Returns a table name for an entity class.
     *
     * @param class-string $className
     */
    public function classToTableName(string $className): string;

    /**
     * Returns a column name for a property.
     *
     * @param class-string $className
     */
    public function propertyToColumnName(string $propertyName, string $className): string;

    /**
     * Returns a column name for an embedded property.
     *
     * @param class-string $className
     * @param class-string $embeddedClassName
     */
    public function embeddedFieldToColumnName(
        string $propertyName,
        string $embeddedColumnName,
        string $className,
        string $embeddedClassName,
    ): string;

    /**
     * Returns the default reference column name.
     */
    public function referenceColumnName(): string;

    /**
     * Returns a join column name for a property.
     *
     * @param class-string $className
     */
    public function joinColumnName(string $propertyName, string $className): string;

    /**
     * Returns a join table name.
     *
     * @param class-string $sourceEntity
     * @param class-string $targetEntity
     */
    public function joinTableName(string $sourceEntity, string $targetEntity, string $propertyName): string;

    /**
     * Returns the foreign key column name for the given parameters.
     *
     * @param class-string $entityName           An entity.
     * @param string|null  $referencedColumnName A property name or null in
     *                                           case of a self-referencing
     *                                           entity with join columns
     *                                           defined in the mapping
     */
    public function joinKeyColumnName(string $entityName, string|null $referencedColumnName): string;
}
