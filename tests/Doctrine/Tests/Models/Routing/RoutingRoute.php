<?php

namespace Doctrine\Tests\Models\Routing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class RoutingRoute
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="RoutingLeg", cascade={"all"})
     * @ORM\JoinTable(name="RoutingRouteLegs",
     *     joinColumns={@ORM\JoinColumn(name="route_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="leg_id", referencedColumnName="id", unique=true)}
     * )
     * @ORM\OrderBy({"departureDate" = "ASC"})
     */
    public $legs;

    /**
     * @ORM\OneToMany(targetEntity="RoutingRouteBooking", mappedBy="route")
     * @ORM\OrderBy({"passengerName" = "ASC"})
     */
    public $bookings = [];

    public function __construct()
    {
        $this->legs = new ArrayCollection();
    }
}
