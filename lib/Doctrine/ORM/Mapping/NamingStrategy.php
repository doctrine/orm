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
     *
     * @return string A table name.
     */
    public function classToTableName($className);

    /**
     * Returns a column name for a property.
     *
     * @param string       $propertyName A property name.
     * @param class-string $className    The fully-qualified class name.
     *
     * @return string A column name.
     */
    public function propertyToColumnName($propertyName, $className = null);

    /**
     * Returns a column name for an embedded property.
     *
     * @param string       $propertyName
     * @param string       $embeddedColumnName
     * @param class-string $className
     * @param class-string $embeddedClassName
     *
     * @return string
     */
    public function embeddedFieldToColumnName(
        $propertyName,
        $embeddedColumnName,
        $className = null,
        $embeddedClassName = null
    );

    /**
     * Returns the default reference column name.
     *
     * @return string A column name.
     */
    public function referenceColumnName();

    /**
     * Returns a join column name for a property.
     *
     * @param string $propertyName A property name.
     *
     * @return string A join column name.
     */
    public function joinColumnName($propertyName/*, string $className */);

    /**
     * Returns a join table name.
     *
     * @param class-string $sourceEntity The source entity.
     * @param class-string $targetEntity The target entity.
     * @param string       $propertyName A property name.
     *
     * @return string A join table name.
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null);

    /**
     * Returns the foreign key column name for the given parameters.
     *
     * @param class-string $entityName           An entity.
     * @param string|null  $referencedColumnName A property name or null in
     *                                           case of a self-referencing
     *                                           entity with join columns
     *                                           defined in the mapping
     *
     * @return string A join column name.
     */
    public function joinKeyColumnName($entityName, $referencedColumnName = null);
}
