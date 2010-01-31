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
     * @generatedValue(strategy="AUTO")
     * @column(type="integer")
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\Routing\RoutingLeg", cascade={"all"})
     * @JoinTable(name="RoutingRouteLegs",
     *     joinColumns={@JoinColumn(name="route_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="leg_id", referencedColumnName="id", unique=true)}
     * )
     */
    public $legs;

    public function __construct()
    {
        $this->legs = new ArrayCollection();
    }
}