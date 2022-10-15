<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

use function is_object;

abstract class AbstractChangeDetectorByClone implements ChangeDetector
{
    /** @inheritDoc */
    public function copyOriginalValue(&$originalValue)
    {
        if (is_object($originalValue)) {
            return clone $originalValue;
        }

        return null;
    }
}
