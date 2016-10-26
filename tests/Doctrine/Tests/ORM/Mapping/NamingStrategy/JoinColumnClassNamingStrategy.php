<?php

namespace Doctrine\Tests\ORM\Mapping\NamingStrategy;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;

/**
 * Stub naming strategy to verify `joinColumnName` proper behavior
 */
class JoinColumnClassNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function joinColumnName($propertyName, $className = null)
    {
        return strtolower($this->classToTableName($className))
            . '_' . $propertyName
            . '_' . $this->referenceColumnName();
    }
}
