<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC1181Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1181Hotel'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1181Booking'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1181Room'),
        ));
    }

    /**
     * @group DDC-1181
     */
    public function testIssue()
    {
        $hotel = new DDC1181Hotel();
        $room1 = new DDC1181Room();
        $room2 = new DDC1181Room();

        $this->_em->persist($hotel);
        $this->_em->persist($room1);
        $this->_em->persist($room2);
        $this->_em->flush();

        $booking1 = new DDC1181Booking;
        $booking1->hotel = $hotel;
        $booking1->room = $room1;
        $booking2 = new DDC1181Booking;
        $booking2->hotel = $hotel;
        $booking2->room = $room2;
        $hotel->bookings[] = $booking1;
        $hotel->bookings[] = $booking2;

        $this->_em->persist($booking1);
        $this->_em->persist($booking2);
        $this->_em->flush();

        $this->_em->remove($hotel);
        $this->_em->flush();
    }
}

/**
 * @Entity
 */
class DDC1181Hotel
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @oneToMany(targetEntity="DDC1181Booking", mappedBy="hotel", cascade={"remove"})
     * @var Booking[]
     */
    public $bookings;

}

/**
 * @Entity
 */
class DDC1181Booking
{
    /**
     * @var Hotel
     *
     * @Id
     * @ManyToOne(targetEntity="DDC1181Hotel", inversedBy="bookings")
     * @JoinColumns({
     *   @JoinColumn(name="hotel_id", referencedColumnName="id")
     * })
     */
    public $hotel;
    /**
     * @var Room
     *
     * @Id
     * @ManyToOne(targetEntity="DDC1181Room")
     * @JoinColumns({
     *   @JoinColumn(name="room_id", referencedColumnName="id")
     * })
     */
    public $room;
}

/**
 * @Entity
 */
class DDC1181Room
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}