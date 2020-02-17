<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

interface Exporter
{
    public const INDENTATION = '    ';

    /**
     * @param mixed $value
     */
    public function export($value, int $indentationLevel = 0) : string;
}
