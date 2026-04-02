<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH12225;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class MyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        return $targetTableAlias . '.deleted_at IS NULL';
    }
}
