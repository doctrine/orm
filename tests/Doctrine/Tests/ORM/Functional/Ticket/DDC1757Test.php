<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

class DDC1757Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testFailingCase()
    {
        $qb = $this->_em->createQueryBuilder();
        /* @var $qb \Doctrine\ORM\QueryBuilder */

        $qb->select('_a')
            ->from(__NAMESPACE__ . '\DDC1757A', '_a')
            ->from(__NAMESPACE__ . '\DDC1757B', '_b')
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
 * @Entity
 */
class DDC1757A
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
}

/**
 * @Entity
 */
class DDC1757B
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="DDC1757C")
     */
    private $c;
}

/**
 * @Entity
 */
class DDC1757C
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToOne(targetEntity="DDC1757D")
     */
    private $d;
}

/**
 * @Entity
 */
class DDC1757D
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}
