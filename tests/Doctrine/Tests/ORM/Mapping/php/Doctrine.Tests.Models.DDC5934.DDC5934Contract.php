<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping;

$association = new Mapping\ManyToManyAssociationMetadata('members');

$association->setFetchMode(Mapping\FetchMode::EXTRA_LAZY);

$metadata->setPropertyOverride($association);
