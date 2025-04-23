<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilterAndIndexedRelation;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

use function sprintf;

class CategoryTypeSQLFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->getName() === Category::class) {
            return sprintf('%s.%s = %s', $targetTableAlias, $targetEntity->fieldMappings['type']->fieldName, $this->getParameter('type'));
        }

        return '';
    }
}
