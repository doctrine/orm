<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\SQLFilter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\Insurance;

use function sprintf;

class PracticeContextSQLFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (! $this->hasParameter('practiceId') || $this->getParameter('practiceId') === null) {
            return '';
        }

        if ($targetEntity->getName() === Insurance::class) {
            return sprintf(
                '%s.%s = %s',
                $targetTableAlias,
                $targetEntity->associationMappings['practice']['joinColumns'][0]['name'],
                $this->getParameter('practiceId')
            );
        }

        return '';
    }
}
