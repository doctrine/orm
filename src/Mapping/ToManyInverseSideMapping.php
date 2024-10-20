<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

abstract class ToManyInverseSideMapping extends InverseSideMapping implements ToManyAssociationMapping
{
    use ToManyAssociationMappingImplementation;
}
