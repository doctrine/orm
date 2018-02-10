<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use function assert;
use function sprintf;

class OneToOneAssociationMetadataExporter extends ToOneAssociationMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    protected function exportInstantiation(AssociationMetadata $metadata) : string
    {
        assert($metadata instanceof OneToOneAssociationMetadata);

        return sprintf(
            'new Mapping\OneToOneAssociationMetadata("%s");',
            $metadata->getName()
        );
    }
}
