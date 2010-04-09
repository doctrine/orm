<?php

namespace Doctrine\Tests\Models\Routing;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class RoutingRoute
{
    /**
     * @Id
     * @GeneratedValue
     * @column(type="integer")
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="RoutingLeg", cascade={"all"})
     * @JoinTable(name="RoutingRouteLegs",
     *     joinColumns={@JoinColumn(name="route_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="leg_id", referencedColumnName="id", unique=true)}
     * )
     * @OrderBy({"departureDate" = "ASC"})
     */
    public $legs;

    /**
     * @OneToMany(targetEntity="RoutingRouteBooking", mappedBy="route")
     * @OrderBy({"passengerName" = "ASC"})
     */
    public $bookings = array();

    public function __construct()
    {
        $this->legs = new ArrayCollection();
    }
}