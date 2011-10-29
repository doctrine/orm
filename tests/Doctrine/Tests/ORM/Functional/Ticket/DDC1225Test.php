<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\Models\CMS\CmsEmployee;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1225
 */
class DDC1225Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1225_TestEntity1'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1225_TestEntity2'),
            ));
        } catch(\PDOException $e) {

        }
    }

    public function testIssue()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->from('Doctrine\Tests\ORM\Functional\Ticket\DDC1225_TestEntity1', 'te1')
           ->select('te1')
           ->where('te1.testEntity2 = ?1')
           ->setParameter(1, 0);

        $this->assertEquals(
            strtolower('SELECT t0_.test_entity2_id AS test_entity2_id0 FROM te1 t0_ WHERE t0_.test_entity2_id = ?'),
            strtolower($qb->getQuery()->getSQL())
        );
    }
}

/**
 * @Entity
 * @Table(name="te1")
 */
class DDC1225_TestEntity1
{
    /**
     * @Id
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC1225_TestEntity2")
     * @JoinColumn(name="test_entity2_id", referencedColumnName="id", nullable=false)
     */
    private $testEntity2;

    /**
     * @param DDC1225_TestEntity2 $testEntity2
     */
    public function setTestEntity2(DDC1225_TestEntity2 $testEntity2)
    {
        $this->testEntity2 = $testEntity2;
    }

    /**
     * @return DDC1225_TestEntity2
     */
    public function getTestEntity2()
    {
        return $this->testEntity2;
    }
}

/**
 * @Entity
 * @Table(name="te2")
 */
class DDC1225_TestEntity2
{
    /**
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(type="integer")
     */
    private $id;
}