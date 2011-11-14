<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Routing\RoutingRoute;
use Doctrine\Tests\Models\Routing\RoutingLocation;
use Doctrine\Tests\Models\Routing\RoutingLeg;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToManyUnidirectionalAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $locations = array();

    public function setUp()
    {
        $this->useModelSet('routing');
        parent::setUp();

        $locations = array("Berlin", "Bonn", "Brasilia", "Atlanta");

        foreach ($locations AS $locationName) {
            $location = new RoutingLocation();
            $location->name = $locationName;
            $this->_em->persist($location);
            $this->locations[$locationName] = $location;
        }
        $this->_em->flush();
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

        $this->_em->persist($route);
        $this->_em->flush();
        $this->_em->clear();

        $routes = $this->_em->createQuery(
            "SELECT r, l, f, t FROM Doctrine\Tests\Models\Routing\RoutingRoute r ".
            "JOIN r.legs l JOIN l.fromLocation f JOIN l.toLocation t"
        )->getSingleResult();

        $this->assertEquals(1, count($routes->legs));
        $this->assertEquals("Berlin", $routes->legs[0]->fromLocation->name);
        $this->assertEquals("Bonn", $routes->legs[0]->toLocation->name);
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

        $this->_em->persist($routeA);
        $this->_em->persist($routeB);

        $exceptionThrown = false;
        try {
            // exception depending on the underyling Database Driver
            $this->_em->flush();
        } catch(\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown, "The underlying database driver throws an exception.");
    }
}
