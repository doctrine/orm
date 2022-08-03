<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Routing;

/**
 * @Entity
 */
class RoutingLeg
{
    /**
     * @var int
     * @Id @generatedValue
     * @column(type="integer")
     */
    public $id;

    /**
     * @var RoutingLocation
     * @ManyToOne(targetEntity="RoutingLocation")
     * @JoinColumn(name="from_id", referencedColumnName="id")
     */
    public $fromLocation;

    /**
     * @var RoutingLocation
     * @ManyToOne(targetEntity="RoutingLocation")
     * @JoinColumn(name="to_id", referencedColumnName="id")
     */
    public $toLocation;

    /**
     * @var DateTime
     * @Column(type="datetime")
     */
    public $departureDate;

    /**
     * @var DateTime
     * @Column(type="datetime")
     */
    public $arrivalDate;
}
