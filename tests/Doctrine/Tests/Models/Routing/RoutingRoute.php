<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Routing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 */
class RoutingRoute
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var Collection<int, RoutingLeg>
     * @ManyToMany(targetEntity="RoutingLeg", cascade={"all"})
     * @JoinTable(name="RoutingRouteLegs",
     *     joinColumns={@JoinColumn(name="route_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="leg_id", referencedColumnName="id", unique=true)}
     * )
     * @OrderBy({"departureDate" = "ASC"})
     */
    public $legs;

    /**
     * @var Collection<int, RoutingRouteBooking>
     * @OneToMany(targetEntity="RoutingRouteBooking", mappedBy="route")
     * @OrderBy({"passengerName" = "ASC"})
     */
    public $bookings = [];

    public function __construct()
    {
        $this->legs = new ArrayCollection();
    }
}
