<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function strtolower;

#[Group('DDC-1225')]
class DDC1225Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC1225TestEntity1::class,
            DDC1225TestEntity2::class,
        );
    }

    public function testIssue(): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->from(DDC1225TestEntity1::class, 'te1')
           ->select('te1')
           ->where('te1.testEntity2 = ?1')
           ->setParameter(1, 0);

        self::assertEquals(
            strtolower('SELECT t0_.test_entity2_id AS test_entity2_id_0 FROM te1 t0_ WHERE t0_.test_entity2_id = ?'),
            strtolower($qb->getQuery()->getSQL()),
        );
    }
}

#[Table(name: 'te1')]
#[Entity]
class DDC1225TestEntity1
{
    #[Id]
    #[ManyToOne(targetEntity: 'Doctrine\Tests\ORM\Functional\Ticket\DDC1225TestEntity2')]
    #[JoinColumn(name: 'test_entity2_id', referencedColumnName: 'id', nullable: false)]
    private DDC1225TestEntity2|null $testEntity2 = null;

    public function setTestEntity2(DDC1225TestEntity2 $testEntity2): void
    {
        $this->testEntity2 = $testEntity2;
    }

    public function getTestEntity2(): DDC1225TestEntity2
    {
        return $this->testEntity2;
    }
}

#[Table(name: 'te2')]
#[Entity]
class DDC1225TestEntity2
{
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    #[Column(type: 'integer')]
    private int $id;
}
