<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Cache;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Table('cache_flight')]
#[Entity]
#[Cache('NONSTRICT_READ_WRITE')]
class Flight
{
    /** @var DateTimeImmutable */
    #[Column(type: 'date_immutable')]
    protected $departure;

    public function __construct(
        #[Id]
        #[Cache]
        #[ManyToOne(targetEntity: 'City')]
        #[JoinColumn(name: 'leaving_from_city_id', referencedColumnName: 'id')]
        protected City $leavingFrom,
        #[Id]
        #[Cache]
        #[ManyToOne(targetEntity: 'City')]
        #[JoinColumn(name: 'going_to_city_id', referencedColumnName: 'id')]
        protected City $goingTo,
    ) {
        $this->departure = new DateTimeImmutable();
    }

    public function getLeavingFrom(): City
    {
        return $this->leavingFrom;
    }

    public function getGoingTo(): City
    {
        return $this->goingTo;
    }

    public function getDeparture(): DateTimeImmutable
    {
        return $this->departure;
    }

    public function setDeparture(DateTimeImmutable $departure): void
    {
        $this->departure = $departure;
    }
}
