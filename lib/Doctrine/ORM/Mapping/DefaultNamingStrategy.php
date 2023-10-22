<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use function str_contains;
use function strrpos;
use function strtolower;
use function substr;

/**
 * The default NamingStrategy
 *
 * @link    www.doctrine-project.org
 */
class DefaultNamingStrategy implements NamingStrategy
{
    /**
     * {@inheritDoc}
     */
    public function classToTableName($className)
    {
        if (str_contains($className, '\\')) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

    /**
     * {@inheritDoc}
     */
    public function propertyToColumnName($propertyName, $className = null)
    {
        return $propertyName;
    }

    /**
     * {@inheritDoc}
     */
    public function embeddedFieldToColumnName($propertyName, $embeddedColumnName, $className = null, $embeddedClassName = null)
    {
        return $propertyName . '_' . $embeddedColumnName;
    }

    /**
     * {@inheritDoc}
     */
    public function referenceColumnName()
    {
        return 'id';
    }

    /**
     * {@inheritDoc}
     *
     * @param string       $propertyName
     * @param class-string $className
     */
    public function joinColumnName($propertyName, $className = null)
    {
        return $propertyName . '_' . $this->referenceColumnName();
    }

    /**
     * {@inheritDoc}
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return strtolower($this->classToTableName($sourceEntity) . '_' .
            $this->classToTableName($targetEntity));
    }

    /**
     * {@inheritDoc}
     */
    public function joinKeyColumnName($entityName, $referencedColumnName = null)
    {
        return strtolower($this->classToTableName($entityName) . '_' .
            ($referencedColumnName ?: $this->referenceColumnName()));
    }
}
