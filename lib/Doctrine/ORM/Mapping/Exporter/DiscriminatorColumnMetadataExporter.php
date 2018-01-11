<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\DiscriminatorColumnMetadata;

class DiscriminatorColumnMetadataExporter extends LocalColumnMetadataExporter
{
    public const VARIABLE = '$discriminatorColumn';

    protected function exportInstantiation(DiscriminatorColumnMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\DiscriminatorColumnMetadata("%s", Type::getType("%s"));',
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );
    }
}
