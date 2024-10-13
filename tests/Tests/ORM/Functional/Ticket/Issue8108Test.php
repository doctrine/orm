<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\OrmFunctionalTestCase;

class Issue8108Test extends OrmFunctionalTestCase
{
    public function testIssue(): void
    {
        $this->createSchemaForModels(
            Issue8108User::class,
            Issue8108Base::class,
            Issue8108Extending::class,
        );
    }
}

#[Entity]
class Issue8108User
{
    public function __construct(
        #[Id]
        #[Column]
        public int $id,
    ) {
    }
}

abstract class Issue8108WithRelation
{
    #[ManyToOne(targetEntity: Issue8108User::class)]
    public Issue8108User|null $createdBy;
}

#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorMap(['extending' => Issue8108Extending::class])]
abstract class Issue8108Base extends Issue8108WithRelation
{
    #[Id]
    #[Column]
    public int $id;
}

#[Entity]
class Issue8108Extending extends Issue8108Base
{
}
