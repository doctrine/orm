<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

abstract class ToManyOwningSideMapping extends OwningSideMapping
{
    use ToManyAssociationMappingImplementation;
}
