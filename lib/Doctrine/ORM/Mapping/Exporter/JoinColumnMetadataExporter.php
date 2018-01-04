<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\JoinColumnMetadata;

class JoinColumnMetadataExporter extends ColumnMetadataExporter
{
    public const VARIABLE = '$joinColumn';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var JoinColumnMetadata $value */
        $indentation     = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference = $indentation . static::VARIABLE;
        $lines           = [];

        $lines[] = parent::export($value, $indentationLevel);
        $lines[] = $objectReference . '->setReferencedColumnName("' . $value->getReferencedColumnName() . '");';
        $lines[] = $objectReference . '->setAliasedName("' . $value->getAliasedName() . '");';
        $lines[] = $objectReference . '->setOnDelete("' . $value->getOnDelete() . '");';

        return implode(PHP_EOL, $lines);
    }

    protected function exportInstantiation(JoinColumnMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\JoinColumnMetadata("%s", Type::getType("%s"));',
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );
    }
}
