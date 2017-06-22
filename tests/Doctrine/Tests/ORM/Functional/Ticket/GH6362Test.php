<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6362Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(GH6362Start::class),
                $this->em->getClassMetadata(GH6362Base::class),
                $this->em->getClassMetadata(GH6362Child::class),
                $this->em->getClassMetadata(GH6362Join::class),
            ]
        );
    }

    /**
     * @group 6362
     *
     * SELECT a as base, b, c, d
     * FROM Start a
     * LEFT JOIN a.bases b
     * LEFT JOIN Child c WITH b.id = c.id
     * LEFT JOIN c.joins d
     */
    public function testInheritanceJoinAlias()
    {
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(GH6362Start::class, 'a', 'base');
        $rsm->addJoinedEntityResult(GH6362Base::class, 'b', 'a', 'bases');
        $rsm->addEntityResult(GH6362Child::class, 'c');
        $rsm->addJoinedEntityResult(GH6362Join::class, 'd', 'c', 'joins');

        $rsm->addFieldResult('a', 'id_0', 'id');
        $rsm->addFieldResult('b', 'id_1', 'id');
        $rsm->addFieldResult('c', 'id_2', 'id');
        $rsm->addFieldResult('d', 'id_3', 'id');

        $rsm->addMetaResult('a', 'bases_id_4', 'bases_id', false, Type::getType('integer'));
        $rsm->addMetaResult('b', 'type_5', 'type', false, Type::getType('string'));
        $rsm->addMetaResult('c', 'type_6', 'type', false, Type::getType('string'));
        $rsm->addMetaResult('d', 'child_id_7', 'child_id', false, Type::getType('integer'));

        $rsm->setDiscriminatorColumn('b', 'type_5');
        $rsm->setDiscriminatorColumn('c', 'type_6');

        $resultSet = [
            [
                'id_0' => '1',
                'id_1' => '1',
                'id_2' => '1',
                'id_3' => '1',
                'bases_id_4' => '1',
                'type_5' => 'child',
                'type_6' => 'child',
                'child_id_7' => '1',
            ],
        ];

        $stmt     = new HydratorMockStatement($resultSet);
        $hydrator = new \Doctrine\ORM\Internal\Hydration\ObjectHydrator($this->em);
        $result   = $hydrator->hydrateAll($stmt, $rsm, [Query::HINT_FORCE_PARTIAL_LOAD => true]);

        self::assertInstanceOf(GH6362Start::class, $result[0]['base']);
        self::assertInstanceOf(GH6362Child::class, $result[1][0]);
    }
}

/**
 * @ORM\Entity
 */
class GH6362Start
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH6362Base", inversedBy="starts")
     */
    private $bases;
}

/**
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"child" = "GH6362Child"})
 * @ORM\Entity
 */
abstract class GH6362Base
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="GH6362Start", mappedBy="bases")
     */
    private $starts;
}

/**
 * @ORM\Entity
 */
class GH6362Child extends GH6362Base
{
    /**
     * @ORM\OneToMany(targetEntity="GH6362Join", mappedBy="child")
     */
    private $joins;
}

/**
 * @ORM\Entity
 */
class GH6362Join
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH6362Child", inversedBy="joins")
     */
    private $child;
}
