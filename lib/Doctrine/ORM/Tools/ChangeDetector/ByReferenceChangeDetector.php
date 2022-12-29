<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

/**
 * This is the original behavior of the Doctrine UnitOfWork value change computation
 */
class ByReferenceChangeDetector implements ChangeDetector
{
    /** @inheritDoc */
    public function copyOriginalValue(&$originalValue)
    {
        return $originalValue;
    }

    public function isChanged($value, $originalValue): bool
    {
        return $value !== $originalValue;
    }
}
