<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6589Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6589Room::class),
                $this->_em->getClassMetadata(GH6589RoomType::class),
            ]
        );

        $roomType1 = new GH6589RoomType(1);
        $roomType2 = new GH6589RoomType(2);
        $room = new GH6589Room(1, $roomType1);
        $this->_em->persist($roomType1);
        $this->_em->persist($roomType2);
        $this->_em->persist($room);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testMoveTo()
    {
        /** @var GH6589RoomType $roomType2 */
        $roomType2 = $this->_em->find(GH6589RoomType::class, 2);

        /** @var GH6589Room $room */
        $room = $this->_em->find(GH6589Room::class, 1);
        $room->moveTo($roomType2);

        self::assertEquals(2, $room->roomType->id);
    }
}

/**
 * @Entity
 */
class GH6589Room
{
    /**
     * @var integer
     *
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var GH6589RoomType
     *
     * @ManyToOne(targetEntity=GH6589RoomType::class, inversedBy="rooms")
     */
    public $roomType;

    public function __construct($id, GH6589RoomType $roomType)
    {
        $this->id = $id;
        $this->roomType = $roomType;
        $this->roomType->addRoom($this);
    }

    public function moveTo(GH6589RoomType $roomType)
    {
        $oldRoomType = $this->roomType;
        $this->roomType = $roomType;
        $roomType->addRoom($this);
        $oldRoomType->removeRoom($this);
    }
}

/**
 * @Entity
 */
class GH6589RoomType
{
    /**
     * @var integer
     *
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var GH6589Room[]
     *
     * @OneToMany(targetEntity=GH6589Room::class, mappedBy="roomType")
     */
    public $rooms;

    public function __construct($id)
    {
        $this->id = $id;
        $this->rooms = new ArrayCollection();
    }

    public function addRoom(GH6589Room $room)
    {
        $this->rooms->add($room);
    }

    public function removeRoom(GH6589Room $room)
    {
        $this->rooms->removeElement($room);
    }
}
