<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ManyToOneAssociationMetadata;

class ManyToOneAssociationMetadataExporter extends ToOneAssociationMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    protected function exportInstantiation(AssociationMetadata $metadata) : string
    {
        assert($metadata instanceof ManyToOneAssociationMetadata);

        return sprintf(
            'new Mapping\ManyToOneAssociationMetadata("%s");',
            $metadata->getName()
        );
    }
}
