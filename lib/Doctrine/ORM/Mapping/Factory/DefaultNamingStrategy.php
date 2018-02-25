<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use function strpos;
use function strrpos;
use function strtolower;
use function substr;

/**
 * The default NamingStrategy
 */
class DefaultNamingStrategy implements NamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function classToTableName(string $className) : string
    {
        if (strpos($className, '\\') !== false) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName(string $propertyName, ?string $className = null) : string
    {
        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function embeddedFieldToColumnName(
        string $propertyName,
        string $embeddedColumnName,
        ?string $className = null,
        ?string $embeddedClassName = null
    ) : string
    {
        return $propertyName . '_' . $embeddedColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function referenceColumnName() : string
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function joinColumnName(string $propertyName, ?string $className = null) : string
    {
        return $propertyName . '_' . $this->referenceColumnName();
    }

    /**
     * {@inheritdoc}
     */
    public function joinTableName(string $sourceEntity, string $targetEntity, ?string $propertyName = null) : string
    {
        return strtolower($this->classToTableName($sourceEntity) . '_' .
            $this->classToTableName($targetEntity));
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName(string $entityName, ?string $referencedColumnName = null) : string
    {
        return strtolower(
            $this->classToTableName($entityName) . '_' . ($referencedColumnName ?: $this->referenceColumnName())
        );
    }
}
