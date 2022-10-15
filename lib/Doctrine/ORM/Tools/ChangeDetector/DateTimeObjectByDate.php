<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

use DateTimeInterface;

class DateTimeObjectByDate extends AbstractChangeDetectorByClone
{
    public function isChanged($value, $originalValue): bool
    {
        if ($value instanceof DateTimeInterface && $originalValue instanceof DateTimeInterface) {
            return $value->format('Y-m-d') !== $originalValue->format('Y-m-d');
        }

        return $value !== $originalValue;
    }
}
