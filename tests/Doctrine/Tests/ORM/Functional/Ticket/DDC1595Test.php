<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1595
 * @group DDC-1596
 * @group non-cacheable
 */
class DDC1595Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\DebugStack);

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC1595BaseInheritance::class),
            $this->em->getClassMetadata(DDC1595InheritedEntity1::class),
            $this->em->getClassMetadata(DDC1595InheritedEntity2::class),
            ]
        );
    }

    public function testIssue()
    {
        $e1 = new DDC1595InheritedEntity1();

        $this->em->persist($e1);
        $this->em->flush();
        $this->em->clear();

        $sqlLogger  = $this->em->getConnection()->getConfiguration()->getSQLLogger();
        $repository = $this->em->getRepository(DDC1595InheritedEntity1::class);

        $entity1  = $repository->find($e1->id);

        // DDC-1596
        self::assertSQLEquals(
            'SELECT t0."id" AS id_1, t0."type" FROM "base" t0 WHERE t0."id" = ? AND t0."type" IN (\'Entity1\')',
            $sqlLogger->queries[count($sqlLogger->queries)]['sql']
        );

        $entities = $entity1->getEntities()->getValues();

        self::assertSQLEquals(
            'SELECT t0."id" AS id_1, t0."type" FROM "base" t0 INNER JOIN "entity1_entity2" ON t0."id" = "entity1_entity2"."item" WHERE "entity1_entity2"."parent" = ? AND t0."type" IN (\'Entity2\')',
            $sqlLogger->queries[count($sqlLogger->queries)]['sql']
        );

        $this->em->clear();

        $entity1  = $repository->find($e1->id);

        $entity1->getEntities()->count();

        self::assertSQLEquals(
            'SELECT COUNT(*) FROM "entity1_entity2" t WHERE t."parent" = ?',
            $sqlLogger->queries[count($sqlLogger->queries)]['sql']
        );
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="base")
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "Entity1" = "DDC1595InheritedEntity1",
 *     "Entity2" = "DDC1595InheritedEntity2"
 * })
 */
abstract class DDC1595BaseInheritance
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class DDC1595InheritedEntity1 extends DDC1595BaseInheritance
{
    /**
     * @ORM\ManyToMany(targetEntity="DDC1595InheritedEntity2", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="entity1_entity2",
     *     joinColumns={@ORM\JoinColumn(name="parent", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="item", referencedColumnName="id")}
     * )
     */
    protected $entities;

    public function getEntities()
    {
        return $this->entities;
    }
}

/**
 * @ORM\Entity
 */
class DDC1595InheritedEntity2 extends DDC1595BaseInheritance
{
}
