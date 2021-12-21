<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use Doctrine\Tests\Models\Routing\RoutingLeg;
use Doctrine\Tests\Models\Routing\RoutingLocation;
use Doctrine\Tests\Models\Routing\RoutingRoute;
use Doctrine\Tests\Models\Routing\RoutingRouteBooking;
use Doctrine\Tests\OrmFunctionalTestCase;

class OrderedCollectionTest extends OrmFunctionalTestCase
{
    /** @psalm-var array<string, RoutingLocation> */
    protected $locations = [];

    protected function setUp(): void
    {
        $this->useModelSet('routing');
        parent::setUp();

        $locations = ['Berlin', 'Bonn', 'Brasilia', 'Atlanta'];

        foreach ($locations as $locationName) {
            $location       = new RoutingLocation();
            $location->name = $locationName;
            $this->_em->persist($location);
            $this->locations[$locationName] = $location;
        }

        $this->_em->flush();
    }

    public function createPersistedRouteWithLegs(): int
    {
        $route = new RoutingRoute();

        $leg1                = new RoutingLeg();
        $leg1->fromLocation  = $this->locations['Berlin'];
        $leg1->toLocation    = $this->locations['Bonn'];
        $leg1->departureDate = new DateTime('now');
        $leg1->arrivalDate   = new DateTime('now +5 hours');

        $leg2                = new RoutingLeg();
        $leg2->fromLocation  = $this->locations['Bonn'];
        $leg2->toLocation    = $this->locations['Brasilia'];
        $leg2->departureDate = new DateTime('now +6 hours');
        $leg2->arrivalDate   = new DateTime('now +24 hours');

        $route->legs[] = $leg2;
        $route->legs[] = $leg1;

        $this->_em->persist($route);
        $this->_em->flush();
        $routeId = $route->id;
        $this->_em->clear();

        return $routeId;
    }

    public function testLazyManyToManyCollectionIsRetrievedWithOrderByClause(): void
    {
        $routeId = $this->createPersistedRouteWithLegs();

        $route = $this->_em->find(RoutingRoute::class, $routeId);

        self::assertCount(2, $route->legs);
        self::assertEquals('Berlin', $route->legs[0]->fromLocation->getName());
        self::assertEquals('Bonn', $route->legs[1]->fromLocation->getName());
    }

    public function testLazyOneToManyCollectionIsRetrievedWithOrderByClause(): void
    {
        $route = new RoutingRoute();

        $this->_em->persist($route);
        $this->_em->flush();
        $routeId = $route->id;

        $booking1                = new RoutingRouteBooking();
        $booking1->passengerName = 'Guilherme';
        $booking2                = new RoutingRouteBooking();
        $booking2->passengerName = 'Benjamin';

        $route->bookings[] = $booking1;
        $booking1->route   = $route;
        $route->bookings[] = $booking2;
        $booking2->route   = $route;

        $this->_em->persist($booking1);
        $this->_em->persist($booking2);

        $this->_em->flush();
        $this->_em->clear();

        $route = $this->_em->find(RoutingRoute::class, $routeId);

        self::assertCount(2, $route->bookings);
        self::assertEquals('Benjamin', $route->bookings[0]->getPassengerName());
        self::assertEquals('Guilherme', $route->bookings[1]->getPassengerName());
    }

    public function testOrderedResultFromDqlQuery(): void
    {
        $routeId = $this->createPersistedRouteWithLegs();

        $route = $this->_em->createQuery('SELECT r, l FROM Doctrine\Tests\Models\Routing\RoutingRoute r JOIN r.legs l WHERE r.id = ?1')
                           ->setParameter(1, $routeId)
                           ->getSingleResult();

        self::assertCount(2, $route->legs);
        self::assertEquals('Berlin', $route->legs[0]->fromLocation->getName());
        self::assertEquals('Bonn', $route->legs[1]->fromLocation->getName());
    }
}
