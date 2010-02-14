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
     * @generatedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="RoutingRoute")
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