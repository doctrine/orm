<?php

namespace Doctrine\Tests\Models\Buildings;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="hotel")
 */
class Hotel
{
    /**
     * @Id @Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @OneToMany(targetEntity="Room", mappedBy="hotel")
     * @var Room[]
     */
    public $rooms;
    
    public function __construct($name)
    {
        $this->name = $name;
        $this->rooms = new ArrayCollection();
    }

    public function getName()
    {
        return $this->name;
    }

    public function addRoom(Room $room)
    {
        $this->rooms[$room->getNumber()] = $room;
    }
    public function getRooms()
    {
        return $this->rooms;
    }
}