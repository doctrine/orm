<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ToManyAssociationMetadata;
use const PHP_EOL;
use function implode;
use function str_repeat;

abstract class ToManyAssociationMetadataExporter extends AssociationMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var ToManyAssociationMetadata $value */
        $variableExporter = new VariableExporter();
        $indentation      = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference  = $indentation . static::VARIABLE;
        $lines            = [];

        $lines[] = parent::export($value, $indentationLevel);

        if (! empty($value->getIndexedBy())) {
            $lines[] = $objectReference . '->setIndexedBy("' . $value->getIndexedBy() . '"");';
        }

        $lines[] = $objectReference . '->setOderBy(' . $variableExporter->export($value->getOrderBy(), $indentationLevel + 1) . ');';

        return implode(PHP_EOL, $lines);
    }
}
