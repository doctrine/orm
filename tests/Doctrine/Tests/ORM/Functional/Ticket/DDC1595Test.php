<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-1595
 * @group DDC-1596
 */
class DDC1595Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\DebugStack);

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1595BaseInheritance'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1595InheritedEntity1'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1595InheritedEntity2'),
        ));
    }

    public function testIssue()
    {
        $e1 = new DDC1595InheritedEntity1();

        $this->_em->persist($e1);
        $this->_em->flush();
        $this->_em->clear();

        $sqlLogger  = $this->_em->getConnection()->getConfiguration()->getSQLLogger();
        $repository = $this->_em->getRepository(__NAMESPACE__ . '\\DDC1595InheritedEntity1');

        $entity1  = $repository->find($e1->id);

        // DDC-1596
        $this->assertEquals(
            "SELECT t0.id AS id1, t0.type FROM base t0 WHERE t0.id = ? AND t0.type IN ('Entity1')",
            $sqlLogger->queries[count($sqlLogger->queries)]['sql']
        );

        $entities = $entity1->getEntities()->getValues();

        $this->assertEquals(
            "SELECT t0.id AS id1, t0.type FROM base t0 INNER JOIN entity1_entity2 ON t0.id = entity1_entity2.item WHERE entity1_entity2.parent = ? AND t0.type IN ('Entity2')",
            $sqlLogger->queries[count($sqlLogger->queries)]['sql']
        );

        $this->_em->clear();

        $entity1  = $repository->find($e1->id);
        $entities = $entity1->getEntities()->count();

        $this->assertEquals(
            "SELECT COUNT(*) FROM entity1_entity2 t WHERE parent = ?",
            $sqlLogger->queries[count($sqlLogger->queries)]['sql']
        );
    }
}

/**
 * @Entity
 * @Table(name="base")
 *
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({
 *     "Entity1" = "DDC1595InheritedEntity1",
 *     "Entity2" = "DDC1595InheritedEntity2"
 * })
 */
abstract class DDC1595BaseInheritance
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     *
     * @var integer
     */
    public $id;
}

/**
 * @Entity
 * @Table(name="entity1")
 */
class DDC1595InheritedEntity1 extends DDC1595BaseInheritance
{
    /**
     * @ManyToMany(targetEntity="DDC1595InheritedEntity2", fetch="EXTRA_LAZY")
     * @JoinTable(name="entity1_entity2",
     *     joinColumns={@JoinColumn(name="parent", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="item", referencedColumnName="id")}
     * )
     */
    protected $entities;

    public function getEntities()
    {
        return $this->entities;
    }
}

/**
 * @Entity
 * @Table(name="entity2")
 */
class DDC1595InheritedEntity2 extends DDC1595BaseInheritance
{
}