<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Tests\GetIterableTester;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7496WithGetIterableTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH7496EntityA::class,
                GH7496EntityB::class,
                GH7496EntityAinB::class,
            ]
        );

        $this->_em->persist($a1 = new GH7496EntityA(1, 'A#1'));
        $this->_em->persist($a2 = new GH7496EntityA(2, 'A#2'));
        $this->_em->persist($b1 = new GH7496EntityB(1, 'B#1'));
        $this->_em->persist(new GH7496EntityAinB(1, $a1, $b1));
        $this->_em->persist(new GH7496EntityAinB(2, $a2, $b1));

        $this->_em->flush();
        $this->_em->clear();
    }

    public function testNonUniqueObjectHydrationDuringIteration() : void
    {
        $q = $this->_em->createQuery(
            'SELECT b FROM ' . GH7496EntityAinB::class . ' aib JOIN ' . GH7496EntityB::class . ' b WITH aib.eB = b'
        );

        $bs = GetIterableTester::iterableToArray(
            $q->getIterable([], AbstractQuery::HYDRATE_OBJECT)
        );
        $this->assertCount(2, $bs);
        $this->assertInstanceOf(GH7496EntityB::class, $bs[0]);
        $this->assertInstanceOf(GH7496EntityB::class, $bs[1]);
    }
}

/**
 * @Entity
 */
class GH7496EntityA
{
    /**
     * @Id
     * @Column(type="integer", name="a_id")
     */
    public $id;

    /** @Column(type="string") */
    public $name;

    public function __construct(int $id, string $name)
    {
        $this->id   = $id;
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
     * @Column(type="integer", name="b_id")
     */
    public $id;

    /** @Column(type="string") */
    public $name;

    public function __construct(int $id, $name)
    {
        $this->id   = $id;
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
     * @Column(type="integer")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity=GH7496EntityA::class)
     * @JoinColumn(name="a_id", referencedColumnName="a_id", nullable=false)
     */
    public $eA;

    /**
     * @ManyToOne(targetEntity=GH7496EntityB::class)
     * @JoinColumn(name="b_id", referencedColumnName="b_id", nullable=false)
     */
    public $eB;

    public function __construct(int $id, $a, $b)
    {
        $this->id = $id;
        $this->eA = $a;
        $this->eB = $b;
    }
}
