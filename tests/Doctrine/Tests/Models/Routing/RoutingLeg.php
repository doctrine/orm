<?php

namespace Doctrine\Tests\Models\Routing;

/**
 * @Entity
 */
class RoutingLeg
{
    /**
     * @Id @generatedValue
     * @column(type="integer")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="RoutingLocation")
     * @JoinColumn(name="from_id", referencedColumnName="id")
     */
    public $fromLocation;

    /**
     * @ManyToOne(targetEntity="RoutingLocation")
     * @JoinColumn(name="to_id", referencedColumnName="id")
     */
    public $toLocation;

    /**
     * @Column(type="datetime")
     */
    public $departureDate;

    /**
     * @Column(type="datetime")
     */
    public $arrivalDate;
}