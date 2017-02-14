<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC1181Test extends OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC1181Hotel::class),
                $this->em->getClassMetadata(DDC1181Booking::class),
                $this->em->getClassMetadata(DDC1181Room::class),
            ]
        );
    }

    /**
     * @group DDC-1181
     */
    public function testIssue()
    {
        $hotel = new DDC1181Hotel();
        $room1 = new DDC1181Room();
        $room2 = new DDC1181Room();

        $this->em->persist($hotel);
        $this->em->persist($room1);
        $this->em->persist($room2);
        $this->em->flush();

        $booking1 = new DDC1181Booking;
        $booking1->hotel = $hotel;
        $booking1->room = $room1;
        $booking2 = new DDC1181Booking;
        $booking2->hotel = $hotel;
        $booking2->room = $room2;
        $hotel->bookings[] = $booking1;
        $hotel->bookings[] = $booking2;

        $this->em->persist($booking1);
        $this->em->persist($booking2);
        $this->em->flush();

        $this->em->remove($hotel);
        $this->em->flush();

        self::assertEmpty($this->em->getRepository(DDC1181Booking::class)->findAll());
    }
}

/**
 * @ORM\Entity
 */
class DDC1181Hotel
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC1181Booking", mappedBy="hotel", cascade={"remove"})
     * @var Booking[]
     */
    public $bookings;

}

/**
 * @ORM\Entity
 */
class DDC1181Booking
{
    /**
     * @var Hotel
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC1181Hotel", inversedBy="bookings")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="hotel_id", referencedColumnName="id")
     * })
     */
    public $hotel;
    /**
     * @var Room
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="DDC1181Room")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="room_id", referencedColumnName="id")
     * })
     */
    public $room;
}

/**
 * @ORM\Entity
 */
class DDC1181Room
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}
