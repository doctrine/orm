<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping;

$association = new Mapping\ManyToManyAssociationMetadata('groups');

$association->setInversedBy('admins');

$metadata->setPropertyOverride($association);
