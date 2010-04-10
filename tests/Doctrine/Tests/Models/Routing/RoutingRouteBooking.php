<?php

namespace Doctrine\Tests\Models\Routing;

/**
 * @Entity
 */
class RoutingRouteBooking
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="RoutingRoute", inversedBy="bookings")
     * @JoinColumn(name="route_id", referencedColumnName="id")
     */
    public $route;

    /**
     * @Column(type="string")
     */
    public $passengerName = null;

    public function getPassengerName()
    {
        return $this->passengerName;
    }
}