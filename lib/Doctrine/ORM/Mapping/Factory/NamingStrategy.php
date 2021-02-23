<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

/**
 * A set of rules for determining the physical column and table names
 */
interface NamingStrategy
{
    /**
     * Returns a table name for an entity class.
     *
     * @param string $className The fully-qualified class name.
     *
     * @return string A table name.
     */
    public function classToTableName(string $className) : string;

    /**
     * Returns a column name for a property.
     *
     * @param string      $propertyName A property name.
     * @param string|null $className    The fully-qualified class name.
     *
     * @return string A column name.
     */
    public function propertyToColumnName(string $propertyName, ?string $className = null) : string;

    /**
     * Returns a column name for an embedded property.
     *
     * @param string $propertyName       A property name.
     * @param string $embeddedColumnName An embedded column name.
     * @param string $className          The fully-qualified class name.
     * @param string $embeddedClassName  The fully-qualified embedded class name.
     */
    public function embeddedFieldToColumnName(
        string $propertyName,
        string $embeddedColumnName,
        ?string $className = null,
        ?string $embeddedClassName = null
    ) : string;

    /**
     * Returns the default reference column name.
     *
     * @return string A column name.
     */
    public function referenceColumnName() : string;

    /**
     * Returns a join column name for a property.
     *
     * @param string      $propertyName A property name.
     * @param string|null $className    The fully-qualified class name.
     *
     * @return string A join column name.
     */
    public function joinColumnName(string $propertyName, ?string $className = null) : string;

    /**
     * Returns a join table name.
     *
     * @param string      $sourceEntity The source entity.
     * @param string      $targetEntity The target entity.
     * @param string|null $propertyName A property name.
     *
     * @return string A join table name.
     */
    public function joinTableName(string $sourceEntity, string $targetEntity, ?string $propertyName = null) : string;

    /**
     * Returns the foreign key column name for the given parameters.
     *
     * @param string      $entityName           An entity.
     * @param string|null $referencedColumnName A property.
     *
     * @return string A join column name.
     */
    public function joinKeyColumnName(string $entityName, ?string $referencedColumnName = null) : string;
}
