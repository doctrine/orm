<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\NamingStrategy;

use Doctrine\ORM\Mapping\NamingStrategy;
use LogicException;

use function sprintf;
use function str_contains;
use function strrpos;
use function strtolower;
use function substr;
use function ucfirst;

/**
 * Fully customized naming strategy changing all namings to a PascalCase model. Included to test some behaviours
 * regarding fully custom naming strategies.
 */
class CustomPascalNamingStrategy implements NamingStrategy
{
    /**
     * Returns a table name for an entity class.
     *
     * @param string $className The fully-qualified class name
     *
     * @return string A table name
     */
    public function classToTableName(string $className): string
    {
        if (str_contains($className, '\\')) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

    /**
     * Returns a column name for a property.
     *
     * @param string      $propertyName A property name
     * @param string|null $className    The fully-qualified class name
     *
     * @return string A column name
     */
    public function propertyToColumnName(string $propertyName, string|null $className = null): string
    {
        if ($className !== null && strtolower($propertyName) === strtolower($this->classToTableName($className)) . 'id') {
            return 'Id';
        }

        return ucfirst($propertyName);
    }

    /**
     * Returns a column name for an embedded property.
     */
    public function embeddedFieldToColumnName(string $propertyName, string $embeddedColumnName, string|null $className = null, $embeddedClassName = null): string
    {
        throw new LogicException(sprintf('Method %s is not implemented', __METHOD__));
    }

    /**
     * Returns the default reference column name.
     *
     * @return string A column name
     */
    public function referenceColumnName(): string
    {
        return 'Id';
    }

    /**
     * Returns a join column name for a property.
     *
     * @return string A join column name
     */
    public function joinColumnName(string $propertyName, string $className): string
    {
        return ucfirst($propertyName) . $this->referenceColumnName();
    }

    /**
     * Returns a join table name.
     *
     * @param string      $sourceEntity The source entity
     * @param string      $targetEntity The target entity
     * @param string|null $propertyName A property name
     *
     * @return string A join table name
     */
    public function joinTableName(string $sourceEntity, string $targetEntity, string|null $propertyName = null): string
    {
        return $this->classToTableName($sourceEntity) . $this->classToTableName($targetEntity);
    }

    /**
     * Returns the foreign key column name for the given parameters.
     *
     * @param string      $entityName           An entity
     * @param string|null $referencedColumnName A property
     *
     * @return string A join column name
     */
    public function joinKeyColumnName(string $entityName, string|null $referencedColumnName = null): string
    {
        return $this->classToTableName($entityName) . ($referencedColumnName ?: $this->referenceColumnName());
    }
}
