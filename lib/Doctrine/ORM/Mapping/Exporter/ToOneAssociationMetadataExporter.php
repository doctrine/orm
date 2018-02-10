<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use const PHP_EOL;
use function implode;
use function str_repeat;

abstract class ToOneAssociationMetadataExporter extends AssociationMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var ToOneAssociationMetadata $value */
        $joinColumnExporter = new JoinColumnMetadataExporter();
        $indentation        = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference    = $indentation . static::VARIABLE;
        $lines              = [];

        $lines[] = parent::export($value, $indentationLevel);

        foreach ($value->getJoinColumns() as $joinColumn) {
            $lines[] = null;
            $lines[] = $joinColumnExporter->export($joinColumn, $indentationLevel);
            $lines[] = null;
            $lines[] = $objectReference . '->addJoinColumn(' . $joinColumnExporter::VARIABLE . ');';
        }

        return implode(PHP_EOL, $lines);
    }
}
