<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use DateTime;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_flight")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 */
class Flight
{
    /**
     * @ORM\Id
     * @ORM\Cache
     * @ORM\ManyToOne(targetEntity=City::class)
     * @ORM\JoinColumn(name="leaving_from_city_id", referencedColumnName="id")
     */
    protected $leavingFrom;

    /**
     * @ORM\Id
     * @ORM\Cache
     * @ORM\ManyToOne(targetEntity=City::class)
     * @ORM\JoinColumn(name="going_to_city_id", referencedColumnName="id")
     */
    protected $goingTo;

    /** @ORM\Column(type="date") */
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

    public function setDeparture($departure)
    {
        $this->departure = $departure;
    }
}
