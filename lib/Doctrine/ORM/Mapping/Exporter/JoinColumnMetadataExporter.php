<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use const PHP_EOL;
use function assert;
use function implode;
use function sprintf;
use function str_repeat;

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

    protected function exportInstantiation(ColumnMetadata $metadata) : string
    {
        assert($metadata instanceof JoinColumnMetadata);

        return sprintf(
            'new Mapping\JoinColumnMetadata("%s", Type::getType("%s"));',
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );
    }
}
