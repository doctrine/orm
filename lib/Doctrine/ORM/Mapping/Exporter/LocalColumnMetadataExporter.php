<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\LocalColumnMetadata;
use const PHP_EOL;
use function implode;
use function sprintf;
use function str_repeat;
use function var_export;

abstract class LocalColumnMetadataExporter extends ColumnMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var LocalColumnMetadata $value */
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        $lines[] = parent::export($value, $indentationLevel);

        $lines[] = $objectReference . '->setLength(' . $value->getLength() . ');';
        $lines[] = $objectReference . '->setScale(' . $value->getScale() . ');';
        $lines[] = $objectReference . '->setPrecision(' . $value->getPrecision() . ');';

        if ($value->hasValueGenerator()) {
            $lines[] = sprintf(
                $objectReference . '->setValueGenerator(new ValueGenerator(%s, %s));',
                var_export($value->getValueGenerator()->getType(), true),
                var_export($value->getValueGenerator()->getDefinition(), true)
            );
        }

        return implode(PHP_EOL, $lines);
    }
}
