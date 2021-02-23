<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use const PHP_EOL;
use function array_keys;
use function array_reduce;
use function implode;
use function is_array;
use function is_numeric;
use function ltrim;
use function sprintf;
use function str_pad;
use function str_repeat;
use function strlen;
use function var_export;

class VariableExporter implements Exporter
{
    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        if (! is_array($value)) {
            return var_export($value, true);
        }

        $indentation  = str_repeat(self::INDENTATION, $indentationLevel);
        $longestKey   = array_reduce(array_keys($value), static function ($k, $v) {
            return (string) (strlen((string) $k) > strlen((string) $v) ? $k : $v);
        });
        $maxKeyLength = strlen($longestKey) + (is_numeric($longestKey) ? 0 : 2);

        $lines = [];

        $lines[] = $indentation . '[';

        foreach ($value as $entryKey => $entryValue) {
            $lines[] = sprintf(
                '%s%s => %s,',
                $indentation . self::INDENTATION,
                str_pad(var_export($entryKey, true), $maxKeyLength),
                ltrim($this->export($entryValue, $indentationLevel + 1))
            );
        }

        $lines[] = $indentation . ']';

        return implode(PHP_EOL, $lines);
    }
}
