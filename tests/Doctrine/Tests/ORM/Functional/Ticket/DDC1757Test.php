<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC1757Test extends OrmFunctionalTestCase
{
    public function testFailingCase()
    {
        $qb = $this->em->createQueryBuilder();
        /* @var $qb \Doctrine\ORM\QueryBuilder */

        $qb->select('_a')
            ->from(DDC1757A::class, '_a')
            ->from(DDC1757B::class, '_b')
            ->join('_b.c', '_c')
            ->join('_c.d', '_d');

        $q = $qb->getQuery();
        $dql = $q->getDQL();

        // Show difference between expected and actual queries on error
        self::assertEquals("SELECT _a FROM " . __NAMESPACE__ . "\DDC1757A _a, " . __NAMESPACE__ . "\DDC1757B _b INNER JOIN _b.c _c INNER JOIN _c.d _d",
                $dql,
                "Wrong DQL query");
    }
}

/**
 * @ORM\Entity
 */
class DDC1757A
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
}

/**
 * @ORM\Entity
 */
class DDC1757B
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="DDC1757C")
     */
    private $c;
}

/**
 * @ORM\Entity
 */
class DDC1757C
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="DDC1757D")
     */
    private $d;
}

/**
 * @ORM\Entity
 */
class DDC1757D
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
}
