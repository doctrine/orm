<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

use function json_encode;

class ValueByJson extends AbstractChangeDetectorByClone
{
    public function isChanged($value, $originalValue): bool
    {
        $value         = $value === null ? null : json_encode($value);
        $originalValue = $originalValue === null ? null : json_encode($originalValue);

        return $value !== $originalValue;
    }
}
