<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class ToOneAssociationMapping extends AssociationMapping
{
    /** @var array<string, string> */
    public array|null $sourceToTargetKeyColumns = null;

    /** @var array<string, string> */
    public array|null $targetToSourceKeyColumns = null;
}
