<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\NamingStrategy;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;

use function strtolower;

/**
 * Stub naming strategy to verify `joinColumnName` proper behavior
 */
class JoinColumnClassNamingStrategy extends DefaultNamingStrategy
{
    public function joinColumnName(string $propertyName, string|null $className = null): string
    {
        return strtolower($this->classToTableName($className))
            . '_' . $propertyName
            . '_' . $this->referenceColumnName();
    }
}
