<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-3609
 */
class DDC3609Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3609Order'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3609SimpleOrder'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3609ComplexOrder'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3609DeliveryLocation'),
        ));
    }

    /**
     * Verifies that class table inheritance joins work correctly when additional
     * restrictions are placed on the joins for child tables.
     */
    public function testIssue()
    {
        $simpleOrder = new DDC3609SimpleOrder();
        $simpleOrder->fulfiller = 'FulfillerUser';

        $complexOrder = new DDC3609ComplexOrder();
        $deliverTo1 = new DDC3609DeliveryLocation($complexOrder);
        $deliverTo1->location = 'Room 100';
        $deliverTo1->fulfiller = 'FulfillerUser';

        $shouldNotAppear = new DDC3609SimpleOrder();

        $this->_em->persist($simpleOrder);
        $this->_em->persist($shouldNotAppear);
        $this->_em->persist($complexOrder);
        $this->_em->flush();

        $dql = "
        SELECT
          BaseOrder

        FROM
          \Doctrine\Tests\ORM\Functional\Ticket\DDC3609Order BaseOrder
          LEFT JOIN \Doctrine\Tests\ORM\Functional\Ticket\DDC3609SimpleOrder SimpleOrder WITH SimpleOrder.id = BaseOrder.id
          LEFT JOIN \Doctrine\Tests\ORM\Functional\Ticket\DDC3609ComplexOrder ComplexOrder WITH ComplexOrder.id = BaseOrder.id

          LEFT JOIN ComplexOrder.deliveryLocations ComplexDeliveryLocation

        WHERE
          (
            BaseOrder INSTANCE OF \Doctrine\Tests\ORM\Functional\Ticket\DDC3609SimpleOrder
            AND
            SimpleOrder.fulfiller = 'FulfillerUser'
          )
          OR
          (
            BaseOrder INSTANCE OF \Doctrine\Tests\ORM\Functional\Ticket\DDC3609ComplexOrder
            AND
            ComplexDeliveryLocation.fulfiller = 'FulfillerUser'
          )
        ";

        $query = $this->_em->createQuery($dql);
        $results = $query->execute();

        $foundSimple = false;
        $foundComplex = false;
        foreach ($results as $result) {
            if ($result->id == $shouldNotAppear->id) {
                $this->fail('Order was present in results when it should not have been');
            }
            if ($result->id == $simpleOrder->id) {
                $foundSimple = true;
            }
            if ($result->id == $complexOrder->id) {
                $foundComplex = true;
            }
        }

        $this->assertTrue($foundSimple, 'Simple order was not found in search results');
        $this->assertTrue($foundComplex, 'Complex order was not found in search results');
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *  "BaseOrder" = "DDC3609Order",
 *  "SimpleOrder" = "DDC3609SimpleOrder",
 *  "ComplexOrder" = "DDC3609ComplexOrder"
 * })
 */
class DDC3609Order
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    /** @Column(type="string", nullable=true) */
    public $requester;
}

/**
 * @Entity
 */
class DDC3609SimpleOrder extends DDC3609Order
{
    /**
     * @Column(type="string", nullable=true)
     */
    public $fulfiller;
}

/**
 * @Entity
 */
class DDC3609ComplexOrder extends DDC3609Order
{
    /**
     * @var DDC2306UserAddress[]|\Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="DDC3609DeliveryLocation", mappedBy="order", cascade={"persist"})
     */
    public $deliveryLocations;

    public function __construct() {
        $this->deliveryLocations = new ArrayCollection();
    }
}

/**
 * @Entity
 */
class DDC3609DeliveryLocation
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC3609ComplexOrder") */
    public $order;

    /** @Column(type="string", nullable=true) */
    public $location;

    /** @Column(type="string", nullable=true) */
    public $fulfiller;

    public function __construct(DDC3609ComplexOrder $order)
    {
        $this->order = $order;
        $order->deliveryLocations->add($this);
    }
}


