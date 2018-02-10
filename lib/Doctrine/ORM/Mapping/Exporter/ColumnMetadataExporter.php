<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ColumnMetadata;
use const PHP_EOL;
use function implode;
use function ltrim;
use function str_repeat;

abstract class ColumnMetadataExporter implements Exporter
{
    public const VARIABLE = '$column';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var ColumnMetadata $value */
        $variableExporter = new VariableExporter();
        $indentation      = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference  = $indentation . static::VARIABLE;
        $lines            = [];

        $lines[] = $objectReference . ' = ' . $this->exportInstantiation($value);

        if (! empty($value->getColumnDefinition())) {
            $lines[] = $objectReference . '->setColumnDefinition("' . $value->getColumnDefinition() . '");';
        }

        $lines[] = $objectReference . '->setTableName("' . $value->getTableName() . '");';
        $lines[] = $objectReference . '->setOptions(' . ltrim($variableExporter->export($value->getOptions(), $indentationLevel + 1)) . ');';
        $lines[] = $objectReference . '->setPrimaryKey(' . ltrim($variableExporter->export($value->isPrimaryKey(), $indentationLevel + 1)) . ');';
        $lines[] = $objectReference . '->setNullable(' . ltrim($variableExporter->export($value->isNullable(), $indentationLevel + 1)) . ');';
        $lines[] = $objectReference . '->setUnique(' . ltrim($variableExporter->export($value->isUnique(), $indentationLevel + 1)) . ');';

        return implode(PHP_EOL, $lines);
    }

    abstract protected function exportInstantiation(ColumnMetadata $metadata) : string;
}
