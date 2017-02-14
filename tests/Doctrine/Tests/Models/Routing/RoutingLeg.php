<?php

namespace Doctrine\Tests\Models\Routing;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class RoutingLeg
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="RoutingLocation")
     * @ORM\JoinColumn(name="from_id", referencedColumnName="id")
     */
    public $fromLocation;

    /**
     * @ORM\ManyToOne(targetEntity="RoutingLocation")
     * @ORM\JoinColumn(name="to_id", referencedColumnName="id")
     */
    public $toLocation;

    /**
     * @ORM\Column(type="datetime")
     */
    public $departureDate;

    /**
     * @ORM\Column(type="datetime")
     */
    public $arrivalDate;
}