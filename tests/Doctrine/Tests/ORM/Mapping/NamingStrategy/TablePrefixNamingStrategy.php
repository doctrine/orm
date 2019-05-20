<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\NamingStrategy;

use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Naming strategy prefixes fields with a table name.
 */
class TablePrefixNamingStrategy implements NamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function classToTableName($className)
    {
        if (strpos($className, '\\') !== false) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName($propertyName, $className = null)
    {
        return $this->classToTableName($className) . '_' . $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function embeddedFieldToColumnName($propertyName, $embeddedColumnName, $className = null, $embeddedClassName = null)
    {
        return $this->classToTableName($className) . '_' . $propertyName . '_' . $embeddedColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function referenceColumnName($targetEntity = null)
    {
        return $this->classToTableName($targetEntity) . '_id';
    }

    /**
     * {@inheritdoc}
     */
    public function joinColumnName($propertyName, $className = null)
    {
        return $this->classToTableName($className) . '_' . $propertyName . '_id';
    }

    /**
     * {@inheritdoc}
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return strtolower($this->classToTableName($sourceEntity) . '_' .
            $this->classToTableName($targetEntity));
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName($entityName, $referencedColumnName = null, $joinTableName = null)
    {
        return $joinTableName . '_' . strtolower($this->classToTableName($entityName) . '_' .
            ($referencedColumnName ?: 'id'));
    }
}
