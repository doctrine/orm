<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping\Fixtures;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    indexes: [new ORM\Index(name: 'bar', columns: ['id'])],
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'foo', columns: ['id'])],
)]
class AttributeEntityWithIndicesAndUniqueConstraintInTableAttribute
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;
}
