<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ManyToManyAssociationMetadata;

class ManyToManyAssociationMetadataExporter extends ToManyAssociationMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var ManyToManyAssociationMetadata $value */
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        $lines[] = parent::export($value, $indentationLevel);

        if ($value->getJoinTable() !== null) {
            $joinTableExporter = new JoinColumnMetadataExporter();

            $lines[] = null;
            $lines[] = $joinTableExporter->export($value->getJoinTable(), $indentationLevel);
            $lines[] = null;
            $lines[] = $objectReference . '->setJoinTable(' . $joinTableExporter::VARIABLE . ');';
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * {@inheritdoc}
     */
    protected function exportInstantiation(ManyToManyAssociationMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\ManyToManyAssociationMetadata("%s");',
            $metadata->getName()
        );
    }
}
