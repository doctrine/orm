<?php

namespace Doctrine\Tests\Models\Routing;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class RoutingRouteBooking
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="RoutingRoute", inversedBy="bookings")
     * @ORM\JoinColumn(name="route_id", referencedColumnName="id")
     */
    public $route;

    /**
     * @ORM\Column(type="string")
     */
    public $passengerName = null;

    public function getPassengerName()
    {
        return $this->passengerName;
    }
}