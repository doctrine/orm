<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use Doctrine\Tests\Models\Routing\RoutingLeg;
use Doctrine\Tests\Models\Routing\RoutingLocation;
use Doctrine\Tests\Models\Routing\RoutingRoute;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToManyUnidirectionalAssociationTest extends OrmFunctionalTestCase
{
    /** @phpstan-var array<string, RoutingLocation> */
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

    public function testPersistOwningInverseCascade(): void
    {
        $leg                = new RoutingLeg();
        $leg->fromLocation  = $this->locations['Berlin'];
        $leg->toLocation    = $this->locations['Bonn'];
        $leg->departureDate = new DateTime('now');
        $leg->arrivalDate   = new DateTime('now +5 hours');

        $route         = new RoutingRoute();
        $route->legs[] = $leg;

        $this->_em->persist($route);
        $this->_em->flush();
        $this->_em->clear();

        $routes = $this->_em->createQuery(
            'SELECT r, l, f, t FROM Doctrine\Tests\Models\Routing\RoutingRoute r ' .
            'JOIN r.legs l JOIN l.fromLocation f JOIN l.toLocation t',
        )->getSingleResult();

        self::assertCount(1, $routes->legs);
        self::assertEquals('Berlin', $routes->legs[0]->fromLocation->name);
        self::assertEquals('Bonn', $routes->legs[0]->toLocation->name);
    }

    public function testLegsAreUniqueToRoutes(): void
    {
        $leg                = new RoutingLeg();
        $leg->fromLocation  = $this->locations['Berlin'];
        $leg->toLocation    = $this->locations['Bonn'];
        $leg->departureDate = new DateTime('now');
        $leg->arrivalDate   = new DateTime('now +5 hours');

        $routeA         = new RoutingRoute();
        $routeA->legs[] = $leg;

        $routeB         = new RoutingRoute();
        $routeB->legs[] = $leg;

        $this->_em->persist($routeA);
        $this->_em->persist($routeB);

        // exception depending on the underlying Database Driver
        $this->expectException(Exception::class);
        $this->_em->flush();
    }
}
