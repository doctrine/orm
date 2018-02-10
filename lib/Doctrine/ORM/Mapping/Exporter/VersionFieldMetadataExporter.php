<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Mapping\VersionFieldMetadata;
use function assert;
use function sprintf;

class VersionFieldMetadataExporter extends FieldMetadataExporter
{
    public const VARIABLE = '$versionProperty';

    protected function exportInstantiation(ColumnMetadata $metadata) : string
    {
        assert($metadata instanceof VersionFieldMetadata);

        return sprintf(
            'new Mapping\VersionFieldMetadata("%s", "%s", Type::getType("%s"));',
            $metadata->getName(),
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );
    }
}
