<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use const PHP_EOL;
use function assert;
use function implode;
use function ltrim;
use function sprintf;
use function str_repeat;

class FieldMetadataExporter extends LocalColumnMetadataExporter
{
    public const VARIABLE = '$property';

    /**
     * {@inheritdoc}
     */
    public function export($value, int $indentationLevel = 0) : string
    {
        /** @var FieldMetadata $value */
        $variableExporter = new VariableExporter();
        $indentation      = str_repeat(self::INDENTATION, $indentationLevel);
        $objectReference  = $indentation . self::VARIABLE;

        $lines   = [];
        $lines[] = parent::export($value, $indentationLevel);
        $lines[] = $objectReference . '->setVersioned(' . ltrim($variableExporter->export($value->isVersioned(), $indentationLevel + 1)) . ');';

        return implode(PHP_EOL, $lines);
    }

    protected function exportInstantiation(ColumnMetadata $metadata) : string
    {
        assert($metadata instanceof FieldMetadata);

        $lines   = [];
        $lines[] = sprintf(
            'new Mapping\FieldMetadata("%s", "%s", Type::getType("%s"));',
            $metadata->getName(),
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );

        return implode(PHP_EOL, $lines);
    }
}
