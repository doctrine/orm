<?php

namespace Doctrine\Tests\Models\Buildings;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="room")
 */
class Room
{

    /**
     * @Id
     * @Column(type="string")
     */
    private $number;

    /**
     * @Id
     * @ManyToOne(targetEntity="Hotel", inversedBy="rooms")
     * @JoinColumn(name="hotel", referencedColumnName="name") 
     * @var Hotel
     */
    private $hotel;

    
    public function __construct(Hotel $hotel, $number)
    {
        $this->number = $number;
        $this->hotel = $hotel;
    }

    public function getHotel()
    {
        return $this->hotel;
    } 
    public function getNumber()
    {
        return $this->number;
    }    
}