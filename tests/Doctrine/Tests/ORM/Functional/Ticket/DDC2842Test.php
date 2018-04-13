<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;

/**
 * @group DDC-2842
 */
class DDC2842Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DC2842Root::class),
                    $this->_em->getClassMetadata(DC2842Child1::class),
                    $this->_em->getClassMetadata(DC2842Child2::class),
                    $this->_em->getClassMetadata(DC2842Owner::class)
                ]
            );
        } catch(\Exception $ignore) {

        }
    }

    public function testSelectConditionSQL()
    {
        $this->_em->getRepository(DC2842Root::class)->find(1);

        $this->assertSQLEquals(
            'SELECT t0.id AS id_1, t0.discr FROM ddc2842_entities t0 WHERE t0.id = ?',
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );

        $this->_em->getRepository(DC2842Child1::class)->find(1);

        $this->assertSQLEquals(
            "SELECT t0.id AS id_1, t0.discr FROM ddc2842_entities t0 WHERE t0.id = ? AND t0.discr IN ('1')",
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );
    }

    public function testSelectConditionCriteriaSQL()
    {
        $relation1 = new DC2842Child1();

        $this->_em->persist($relation1);

        $entity1 = new DC2842Owner();

        $entity1->setRelation($relation1);

        $this->_em->persist($entity1);

        $this->_em->flush();

        $this->_em->clear();

        $entity1 = $this->_em->getRepository(DC2842Owner::class)->find($entity1->getId());

        $relation1 = $entity1->getRelation();

        $this->assertSQLEquals(
            'SELECT t0.id AS id_1, t0.discr FROM ddc2842_entities t0 WHERE t0.id = ?',
            $this->_sqlLoggerStack->queries[$this->_sqlLoggerStack->currentQuery]['sql']
        );

        $this->_em->remove($entity1);
        $this->_em->remove($relation1);

        $this->_em->flush();
    }

    public function testSelectQuerySQL()
    {
        $query = $this->_em->createQuery('SELECT e FROM Doctrine\Tests\ORM\Functional\Ticket\DC2842Root e');
        $this->assertSQLEquals(
            $query->getSQL(),
            'SELECT d0_.id as id_0, d0_.discr as discr_1 FROM ddc2842_entities d0_'
        );
    }
}

/**
 * @Entity
 * @Table(name="ddc2842_entities")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name = "discr", type = "integer", strict = true)
 * @DiscriminatorMap({1 = "DC2842Child1", 2 = "DC2842Child2"})
 */
abstract class DC2842Root
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}

/**
 * @Entity
 */
class DC2842Child1 extends DC2842Root
{
}
/**
 * @Entity
 */
class DC2842Child2 extends DC2842Root
{
}

/**
 * @Entity
 */
class DC2842Owner
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DC2842Root")
     */
    private $relation;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @param mixed $relation
     */
    public function setRelation($relation)
    {
        $this->relation = $relation;
    }
}