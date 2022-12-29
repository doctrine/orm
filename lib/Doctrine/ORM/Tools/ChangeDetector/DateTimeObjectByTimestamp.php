<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

use DateTimeInterface;

class DateTimeObjectByTimestamp extends AbstractChangeDetectorByClone
{
    public function isChanged($value, $originalValue): bool
    {
        if ($value instanceof DateTimeInterface && $originalValue instanceof DateTimeInterface) {
            return $value->getTimestamp() !== $originalValue->getTimestamp();
        }

        return $value !== $originalValue;
    }
}
