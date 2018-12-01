<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7496Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH7496EntityA::class,
                GH7496EntityB::class,
                GH7496EntityAinB::class,
            ]
        );

        $this->_em->persist($a1 = new GH7496EntityA('A#1'));
        $this->_em->persist($a2 = new GH7496EntityA('A#2'));
        $this->_em->persist($b1 = new GH7496EntityB('B#1'));
        $this->_em->persist(new GH7496EntityAinB($a1, $b1));
        $this->_em->persist(new GH7496EntityAinB($a2, $b1));

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testNonUniqueObjectHydrationDuringIteration()
    {
        $q = $this->_em->createQuery(
            'SELECT b FROM ' . GH7496EntityAinB::class . ' aib JOIN ' . GH7496EntityB::class . ' b WITH aib.eB = b'
        );

        $bs = \iterator_to_array(
            $q->iterate(null, \Doctrine\ORM\AbstractQuery::HYDRATE_OBJECT)
        );
        $this->assertCount(2, $bs);
        $this->assertInstanceOf(GH7496EntityB::class, $bs[0][0]);
        $this->assertInstanceOf(GH7496EntityB::class, $bs[0][1]);
    }
}

/**
 * @Entity
 */
class GH7496EntityA
{
    /**
     * @Id
     * @Column(type="string", name="a_id")
     * @GeneratedValue(strategy="UUID")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity
 */
class GH7496EntityB
{
    /**
     * @Id
     * @Column(type="string", name="b_id")
     * @GeneratedValue(strategy="UUID")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity
 */
class GH7496EntityAinB
{
    /**
     * @Id
     * @Column(type="string")
     * @GeneratedValue(strategy="UUID")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="GH7496EntityA")
     * @JoinColumn(name="a_id", referencedColumnName="a_id", nullable=false)
     */
    public $eA;

    /**
     * @ManyToOne(targetEntity="GH7496EntityB")
     * @JoinColumn(name="b_id", referencedColumnName="b_id", nullable=false)
     */
    public $eB;

    public function __construct($a, $b)
    {
        $this->eA = $a;
        $this->eB = $b;
    }
}
