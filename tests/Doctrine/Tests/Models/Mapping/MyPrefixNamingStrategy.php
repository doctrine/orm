<?php

namespace Doctrine\Tests\Models\Mapping;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;

class MyPrefixNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName($propertyName, $className = null)
    {
        return strtolower($this->classToTableName($className)).'_'.$propertyName;
    }
}
