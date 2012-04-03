<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

class DDC1757Test extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
		
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757A'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757B'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757C'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1757D'),
            ));
        } catch(\Exception $ignored) {}
    }

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
		$q->getResult();
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