<?php

namespace Doctrine\Tests\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 6776
 */
class GH6776Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH6776Cost::class, GH6776Vehicle::class]);
    }


    /**
     * Verifies that removing and then inserting an element in a collection using a unique constraint does not
     * make this constraint fail.
     */
    public function testIssue(): void
    {
        // create some vehicle with dimensions
        $vehicle       = new GH6776Vehicle();
        $vehicle->name = 'SuperCar';

        $cost1           = new GH6776Cost();
        $cost1->currency = 'GBP';
        $cost1->amount   = 12000000;
        $cost1->vehicle  = $vehicle;

        $vehicle->cost = $cost1;

        // persist and flush vehicle and the 2 original dimensions
        $this->_em->persist($vehicle);
        $this->_em->persist($cost1);
        $this->_em->flush();

        self::assertEquals($cost1, $vehicle->cost);

        // remove cost1 and add its clone; when flushing it should crash because of unique constraint violation
        $vehicle->cost = null;
        $this->_em->remove($cost1);

        self::assertEquals(null, $vehicle->cost);

        $cost2         = clone $cost1;
        $cost2->amount = 15000000;

        $this->_em->persist($cost2);
        $vehicle->cost = $cost2;

        $this->_em->flush();

        // still the same count
        self::assertEquals($cost2, $vehicle->cost);
    }
}

/**
 * @Entity
 */
class GH6776Vehicle
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @Column(type="string") */
    public $name;

    /** @OneToOne(targetEntity="GH6776Cost", mappedBy="vehicle") */
    public $cost;
}

/**
 * @Entity()
 * @Table(uniqueConstraints={@UniqueConstraint(name="cost", columns={"vehicle_id"})})
 */
class GH6776Cost
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @OneToOne(targetEntity="GH6776Vehicle", inversedBy="code") */
    public $vehicle;

    /** @Column(type="string", length=3) */
    public $currency;

    /** @Column(type="integer") */
    public $amount;
}
