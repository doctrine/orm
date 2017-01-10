<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Routing\RoutingRoute;
use Doctrine\Tests\Models\Routing\RoutingLocation;
use Doctrine\Tests\Models\Routing\RoutingLeg;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToManyUnidirectionalAssociationTest extends OrmFunctionalTestCase
{
    protected $locations = [];

    public function setUp()
    {
        $this->useModelSet('routing');
        parent::setUp();

        $locations = ["Berlin", "Bonn", "Brasilia", "Atlanta"];

        foreach ($locations AS $locationName) {
            $location = new RoutingLocation();
            $location->name = $locationName;
            $this->em->persist($location);
            $this->locations[$locationName] = $location;
        }
        $this->em->flush();
    }

    public function testPersistOwning_InverseCascade()
    {
        $leg = new RoutingLeg();
        $leg->fromLocation = $this->locations['Berlin'];
        $leg->toLocation   = $this->locations['Bonn'];
        $leg->departureDate = new \DateTime("now");
        $leg->arrivalDate = new \DateTime("now +5 hours");

        $route = new RoutingRoute();
        $route->legs[] = $leg;

        $this->em->persist($route);
        $this->em->flush();
        $this->em->clear();

        $routes = $this->em->createQuery(
            "SELECT r, l, f, t FROM Doctrine\Tests\Models\Routing\RoutingRoute r ".
            "JOIN r.legs l JOIN l.fromLocation f JOIN l.toLocation t"
        )->getSingleResult();

        self::assertEquals(1, count($routes->legs));
        self::assertEquals("Berlin", $routes->legs[0]->fromLocation->name);
        self::assertEquals("Bonn", $routes->legs[0]->toLocation->name);
    }

    public function testLegsAreUniqueToRoutes()
    {
        $leg = new RoutingLeg();
        $leg->fromLocation = $this->locations['Berlin'];
        $leg->toLocation   = $this->locations['Bonn'];
        $leg->departureDate = new \DateTime("now");
        $leg->arrivalDate = new \DateTime("now +5 hours");

        $routeA = new RoutingRoute();
        $routeA->legs[] = $leg;

        $routeB = new RoutingRoute();
        $routeB->legs[] = $leg;

        $this->em->persist($routeA);
        $this->em->persist($routeB);

        $exceptionThrown = false;
        try {
            // exception depending on the underlying Database Driver
            $this->em->flush();
        } catch(\Exception $e) {
            $exceptionThrown = true;
        }

        self::assertTrue($exceptionThrown, "The underlying database driver throws an exception.");
    }
}
