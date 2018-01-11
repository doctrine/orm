<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\FieldMetadata;

class FieldMetadataExporter extends LocalColumnMetadataExporter
{
    public const VARIABLE = '$property';

    protected function exportInstantiation(FieldMetadata $metadata) : string
    {
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
