<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;

class OneToOneAssociationMetadataExporter extends ToOneAssociationMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    protected function exportInstantiation(OneToOneAssociationMetadata $metadata) : string
    {
        return sprintf(
            'new Mapping\OneToOneAssociationMetadata("%s");',
            $metadata->getName()
        );
    }
}
