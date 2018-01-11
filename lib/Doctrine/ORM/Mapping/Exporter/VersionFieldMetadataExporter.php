<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\VersionFieldMetadata;

class VersionFieldMetadataExporter extends FieldMetadataExporter
{
    public const VARIABLE = '$versionProperty';

    protected function exportInstantiation(VersionFieldMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\VersionFieldMetadata("%s", "%s", Type::getType("%s"));',
            $metadata->getName(),
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );
    }
}
