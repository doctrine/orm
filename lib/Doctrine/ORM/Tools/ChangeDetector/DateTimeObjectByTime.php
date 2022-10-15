<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

use DateTimeInterface;

class DateTimeObjectByTime extends AbstractChangeDetectorByClone
{
    public function isChanged($value, $originalValue): bool
    {
        if ($value instanceof DateTimeInterface && $originalValue instanceof DateTimeInterface) {
            return $value->format('H:i:s') !== $originalValue->format('H:i:s');
        }

        return $value !== $originalValue;
    }
}
