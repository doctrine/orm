<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\SQLFilter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\Order;
use Doctrine\Tests\ORM\Functional\Ticket\SwitchContextWithFilter\Entity\User;

use function sprintf;

class CompanySQLFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->getName() === User::class) {
            return sprintf('%s.%s = %s', $targetTableAlias, $targetEntity->fieldMappings['company']['fieldName'], $this->getParameter('company'));
        }

        if ($targetEntity->getName() === Order::class) {
            return sprintf('%s.%s = %s', $targetTableAlias, $targetEntity->fieldMappings['company']['fieldName'], $this->getParameter('company'));
        }

        return '';
    }
}
