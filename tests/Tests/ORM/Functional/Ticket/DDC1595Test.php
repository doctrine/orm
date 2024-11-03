<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('DDC-1595')]
#[Group('DDC-1596')]
#[Group('non-cacheable')]
class DDC1595Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC1595BaseInheritance::class,
            DDC1595InheritedEntity1::class,
            DDC1595InheritedEntity2::class,
        );
    }

    public function testIssue(): void
    {
        $e1 = new DDC1595InheritedEntity1();

        $this->_em->persist($e1);
        $this->_em->flush();
        $this->_em->clear();

        $repository = $this->_em->getRepository(DDC1595InheritedEntity1::class);

        $entity1 = $repository->find($e1->id);

        // DDC-1596
        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.type FROM base t0 WHERE t0.id = ? AND t0.type IN ('Entity1')",
            $this->getLastLoggedQuery()['sql'],
        );

        $entities = $entity1->getEntities()->getValues();

        self::assertEquals(
            "SELECT t0.id AS id_1, t0.type FROM base t0 INNER JOIN entity1_entity2 ON t0.id = entity1_entity2.item WHERE entity1_entity2.parent = ? AND t0.type IN ('Entity2')",
            $this->getLastLoggedQuery()['sql'],
        );

        $this->_em->clear();

        $entity1  = $repository->find($e1->id);
        $entities = $entity1->getEntities()->count();

        $this->assertSQLEquals(
            'SELECT COUNT(*) FROM entity1_entity2 t WHERE t.parent = ?',
            $this->getLastLoggedQuery()['sql'],
        );
    }
}

#[Table(name: 'base')]
#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'type', type: 'string')]
#[DiscriminatorMap(['Entity1' => 'DDC1595InheritedEntity1', 'Entity2' => 'DDC1595InheritedEntity2'])]
abstract class DDC1595BaseInheritance
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;
}

#[Table(name: 'entity1')]
#[Entity]
class DDC1595InheritedEntity1 extends DDC1595BaseInheritance
{
    /** @phpstan-var Collection<int, DDC1595InheritedEntity2> */
    #[JoinTable(name: 'entity1_entity2')]
    #[JoinColumn(name: 'parent', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'item', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'DDC1595InheritedEntity2', fetch: 'EXTRA_LAZY')]
    protected $entities;

    /** @phpstan-return Collection<int, DDC1595InheritedEntity2> */
    public function getEntities(): Collection
    {
        return $this->entities;
    }
}

#[Table(name: 'entity2')]
#[Entity]
class DDC1595InheritedEntity2 extends DDC1595BaseInheritance
{
}
