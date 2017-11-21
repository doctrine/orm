<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group 6776
 */
class GH6776Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH6776WheelDimension::class, GH6776Vehicle::class]);
    }

    /**
     * Verifies that removing and then inserting an element in a collection using a unique constraint does not
     * make this constraint fail.
     */
    public function testIssue() : void
    {
        // create some vehicle with dimensions
        $vehicle       = new GH6776Vehicle();
        $vehicle->name = 'SuperCar';

        $dimension1               = new GH6776WheelDimension();
        $dimension1->width        = 6;
        $dimension1->diameter     = 17;
        $dimension1->offset       = 32;
        $dimension1->tirePressure = 2.75;
        $dimension1->vehicle      = $vehicle;

        $dimension1bis               = clone $dimension1;
        $dimension1bis->tirePressure = 3.10;

        $dimension2               = new GH6776WheelDimension();
        $dimension2->type         = 'rear';
        $dimension2->width        = 6;
        $dimension2->diameter     = 17;
        $dimension2->offset       = 39;
        $dimension2->tirePressure = 2.75;
        $dimension2->vehicle      = $vehicle;

        $vehicle->compatibleDimensions = new ArrayCollection();
        $vehicle->compatibleDimensions->add($dimension1);
        $vehicle->compatibleDimensions->add($dimension2);

        // persist and flush vehicle and the 2 original dimensions
        $this->_em->persist($vehicle);
        $this->_em->persist($dimension1);
        $this->_em->persist($dimension2);
        $this->_em->flush();

        self::assertCount(2, $vehicle->compatibleDimensions);

        // remove dimension1 and add its clone; when flushing it should crash because of unique constraint violation
        $vehicle->compatibleDimensions->removeElement($dimension1);
        $this->_em->remove($dimension1);

        self::assertCount(1, $vehicle->compatibleDimensions);

        $this->_em->persist($dimension1bis);
        $vehicle->compatibleDimensions->add($dimension1bis);

        $this->_em->flush();

        // still the same count
        self::assertCount(2, $vehicle->compatibleDimensions);
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

    /** @oneToMany(targetEntity="GH6776WheelDimension", mappedBy="vehicle") */
    public $compatibleDimensions;
}

/**
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="wheel_size", columns={"offset", "width", "diameter"})})
 */
class GH6776WheelDimension
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="GH6776Vehicle", inversedBy="compatibleDimensions") */
    public $vehicle;

    /** @Column(type="integer") */
    public $offset;

    /** @Column(type="integer") */
    public $width;

    /** @Column(type="integer") */
    public $diameter;

    /** @Column(type="float") */
    public $tirePressure;

    /** @Column(type="string") */
    public $type = 'front';
}
