<?php

use Doctrine\ORM\Mapping;

$association = new Mapping\ManyToManyAssociationMetadata('groups');

$association->setInversedBy('admins');

$metadata->setAssociationOverride($association);
