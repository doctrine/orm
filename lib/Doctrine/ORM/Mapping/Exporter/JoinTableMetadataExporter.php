<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\JoinTableMetadata;
use Doctrine\ORM\Mapping\TableMetadata;
use const PHP_EOL;
use function assert;
use function implode;
use function sprintf;
use function str_repeat;

class JoinTableMetadataExporter extends TableMetadataExporter
{
    public const VARIABLE = '$joinTable';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var JoinTableMetadata $value */
        $joinColumnExporter = new JoinColumnMetadataExporter();
        $indentation        = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference    = $indentation . static::VARIABLE;
        $lines              = [];

        $lines[] = parent::export($value, $indentationLevel);

        foreach ($value->getJoinColumns() as $joinColumn) {
            $lines[] = $joinColumnExporter->export($joinColumn, $indentationLevel);
            $lines[] = $objectReference . '->addJoinColumn(' . $joinColumnExporter::VARIABLE . ');';
        }

        foreach ($value->getInverseJoinColumns() as $inverseJoinColumn) {
            $lines[] = $joinColumnExporter->export($inverseJoinColumn, $indentationLevel);
            $lines[] = $objectReference . '->addInverseJoinColumn(' . $joinColumnExporter::VARIABLE . ');';
        }

        return implode(PHP_EOL, $lines);
    }

    protected function exportInstantiation(TableMetadata $metadata) : string
    {
        assert($metadata instanceof JoinTableMetadata);

        return sprintf(
            'new Mapping\JoinTableMetadata("%s");',
            $metadata->getName()
        );
    }
}
