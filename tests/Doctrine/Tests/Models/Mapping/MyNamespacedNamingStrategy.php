<?php

namespace Doctrine\Tests\Models\Mapping;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;

class MyNamespacedNamingStrategy extends DefaultNamingStrategy
{
    /**
     * {@inheritdoc}
     */
    public function classToTableName($className)
    {
        if (strpos($className, '\\') !== false) {
            $className = str_replace(
                '\\',
                '_',
                str_replace('Doctrine\Tests\Models\\', '', $className)
            );
        }

        return strtolower($className);
    }
}
