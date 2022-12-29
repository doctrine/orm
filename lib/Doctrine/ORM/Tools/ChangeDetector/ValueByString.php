<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

class ValueByString extends AbstractChangeDetectorByClone
{
    public function isChanged($value, $originalValue): bool
    {
        return (string) $value !== (string) $originalValue;
    }
}
