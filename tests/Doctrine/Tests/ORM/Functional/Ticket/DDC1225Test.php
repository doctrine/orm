<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1225
 */
class DDC1225Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1225_TestEntity1::class),
                $this->em->getClassMetadata(DDC1225_TestEntity2::class),
                ]
            );
        } catch(\PDOException $e) {

        }
    }

    public function testIssue()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->from(DDC1225_TestEntity1::class, 'te1')
           ->select('te1')
           ->where('te1.testEntity2 = ?1')
           ->setParameter(1, 0);

        self::assertSQLEquals(
            'SELECT t0_."test_entity2_id" AS test_entity2_id_0 FROM "te1" t0_ WHERE t0_."test_entity2_id" = ?',
            $qb->getQuery()->getSQL()
        );
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="te1")
 */
class DDC1225_TestEntity1
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC1225_TestEntity2")
     * @ORM\JoinColumn(name="test_entity2_id", referencedColumnName="id", nullable=false)
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
 * @ORM\Entity
 * @ORM\Table(name="te2")
 */
class DDC1225_TestEntity2
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;
}
