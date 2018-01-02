<?php


declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

interface Exporter
{
    const INDENTATION = '    ';

    /**
     * @param mixed $value
     * @param int   $indentationLevel
     *
     * @return string
     */
    public function export($value, int $indentationLevel = 0) : string;
}
