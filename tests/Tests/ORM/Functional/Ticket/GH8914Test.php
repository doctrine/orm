<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Group;

final class GH8914Test extends OrmTestCase
{
    #[DoesNotPerformAssertions]
    #[Group('GH-8914')]
    public function testDiscriminatorMapWithSeveralLevelsIsSupported(): void
    {
        $entityManager = $this->getTestEntityManager();
        $entityManager->getClassMetadata(GH8914Person::class);
    }
}

#[MappedSuperclass]
abstract class GH8914BaseEntity
{
}

#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['person' => 'GH8914Person', 'employee' => 'GH8914Employee'])]
class GH8914Person extends GH8914BaseEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;
}

#[Entity]
class GH8914Employee extends GH8914Person
{
}
