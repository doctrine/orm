<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\ColumnMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMetadata;
use function assert;
use function sprintf;

class DiscriminatorColumnMetadataExporter extends LocalColumnMetadataExporter
{
    public const VARIABLE = '$discriminatorColumn';

    protected function exportInstantiation(ColumnMetadata $metadata) : string
    {
        assert($metadata instanceof DiscriminatorColumnMetadata);

        return sprintf(
            'new Mapping\DiscriminatorColumnMetadata("%s", Type::getType("%s"));',
            $metadata->getColumnName(),
            $metadata->getTypeName()
        );
    }
}
