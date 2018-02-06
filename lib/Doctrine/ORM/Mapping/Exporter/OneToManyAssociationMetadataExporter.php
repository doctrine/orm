<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Exporter;

use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\OneToManyAssociationMetadata;
use function assert;
use function sprintf;

class OneToManyAssociationMetadataExporter extends ToManyAssociationMetadataExporter
{
    /**
     * {@inheritdoc}
     */
    protected function exportInstantiation(AssociationMetadata $metadata) : string
    {
        assert($metadata instanceof OneToManyAssociationMetadata);

        return sprintf(
            'new Mapping\OneToManyAssociationMetadata("%s");',
            $metadata->getName()
        );
    }
}
