<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use DateTime;

/**
 * @Entity
 * @Table("cache_flight")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class Flight
{
    /**
     * @Id
     * @Cache
     * @ManyToOne(targetEntity="City")
     * @JoinColumn(name="leaving_from_city_id", referencedColumnName="id")
     */
    protected $leavingFrom;

    /**
     * @Id
     * @Cache
     * @ManyToOne(targetEntity="City")
     * @JoinColumn(name="going_to_city_id", referencedColumnName="id")
     */
    protected $goingTo;

    /** @Column(type="date") */
    protected $departure;

    public function __construct(City $leavingFrom, City $goingTo)
    {
        $this->goingTo     = $goingTo;
        $this->leavingFrom = $leavingFrom;
        $this->departure   = new DateTime();
    }

    public function getLeavingFrom()
    {
        return $this->leavingFrom;
    }

    public function getGoingTo()
    {
        return $this->goingTo;
    }

    public function getDeparture()
    {
        return $this->departure;
    }

    public function setDeparture($departure): void
    {
        $this->departure = $departure;
    }
}
