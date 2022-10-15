<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

interface ChangeDetector
{
    /**
     * How the UnitOfWork should keep trace of the original value
     *
     * @param mixed $originalValue
     *
     * @return mixed
     */
    public function copyOriginalValue(&$originalValue);

    /**
     * Whether or not the two value must be considered as different and trigger an UPDATE query
     *
     * @param mixed $value
     * @param mixed $originalValue
     */
    public function isChanged($value, $originalValue): bool;
}
