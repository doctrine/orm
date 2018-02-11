<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1225
 */
class DDC1225Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1225TestEntity1::class),
                $this->em->getClassMetadata(DDC1225TestEntity2::class),
            ]
        );
    }

    public function testIssue()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->from(DDC1225TestEntity1::class, 'te1')
           ->select('te1')
           ->where('te1.testEntity2 = ?1')
           ->setParameter(1, 0);

        self::assertSQLEquals(
            'SELECT t0."testentity2_id" AS c0 FROM "te1" t0 WHERE t0."testentity2_id" = ?',
            $qb->getQuery()->getSQL()
        );
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="te1")
 */
class DDC1225TestEntity1
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=DDC1225TestEntity2::class)
     * @ORM\JoinColumn(name="testentity2_id", referencedColumnName="id", nullable=false)
     */
    private $testEntity2;

    public function setTestEntity2(DDC1225TestEntity2 $testEntity2)
    {
        $this->testEntity2 = $testEntity2;
    }

    /**
     * @return DDC1225TestEntity2
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
class DDC1225TestEntity2
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;
}
