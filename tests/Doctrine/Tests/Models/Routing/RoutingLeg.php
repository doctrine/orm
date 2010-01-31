<?php

namespace Doctrine\Tests\Models\Routing;

/**
 * @Entity
 */
class RoutingLeg
{
    /**
     * @Id
     * @generatedValue(strategy="AUTO")
     * @column(type="integer")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\Routing\RoutingLocation")
     * @JoinColumn(name="from_id", referencedColumnName="id")
     */
    public $fromLocation;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\Models\Routing\RoutingLocation")
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