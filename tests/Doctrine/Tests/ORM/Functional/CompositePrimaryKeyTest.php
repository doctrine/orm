<?php

namespace Doctrine\Tests\ORM\Functional;
use Doctrine\Tests\Models\Navigation\NavCountry;
use Doctrine\Tests\Models\Navigation\NavPointOfInterest;
use Doctrine\Tests\Models\Navigation\NavTour;

require_once __DIR__ . '/../../TestInit.php';

class CompositePrimaryKeyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('navigation');
        parent::setUp();
    }

    public function putGermanysBrandenburderTor()
    {
        $country = new NavCountry("Germany");
        $this->_em->persist($country);
        $poi = new NavPointOfInterest(100, 200, "Brandenburger Tor", $country);
        $this->_em->persist($poi);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function putTripAroundEurope()
    {
        $poi = $this->_em->find('Doctrine\Tests\Models\Navigation\NavPointOfInterest', array('lat' => 100, 'long' => 200));

        $tour = new NavTour("Trip around Europe");
        $tour->addPointOfInterest($poi);

        $this->_em->persist($tour);
        $this->_em->flush();
        $this->_em->clear();

        return $tour;
    }

    public function testPersistCompositePkEntity()
    {
        $this->putGermanysBrandenburderTor();
        
        $poi = $this->_em->find('Doctrine\Tests\Models\Navigation\NavPointOfInterest', array('lat' => 100, 'long' => 200));

        $this->assertInstanceOf('Doctrine\Tests\Models\Navigation\NavPointOfInterest', $poi);
        $this->assertEquals(100, $poi->getLat());
        $this->assertEquals(200, $poi->getLong());
        $this->assertEquals('Brandenburger Tor', $poi->getName());
    }

    public function testManyToManyCompositeRelation()
    {
        $this->putGermanysBrandenburderTor();
        $tour = $this->putTripAroundEurope();

        $tour = $this->_em->find('Doctrine\Tests\Models\Navigation\NavTour', $tour->getId());

        $this->assertEquals(1, count($tour->getPointOfInterests()));
    }

    public function testCompositeDqlEagerFetching()
    {
        $this->putGermanysBrandenburderTor();
        $this->putTripAroundEurope();

        $dql = 'SELECT t, p, c FROM Doctrine\Tests\Models\Navigation\NavTour t ' .
               'INNER JOIN t.pois p INNER JOIN p.country c';
        $tours = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(1, count($tours));
        
        $pois = $tours[0]->getPointOfInterests();

        $this->assertEquals(1, count($pois));
        $this->assertEquals('Brandenburger Tor', $pois[0]->getName());
    }

    public function testCompositeCollectionMemberExpression()
    {
        $this->markTestSkipped('How to test this?');

        $this->putGermanysBrandenburderTor();
        $this->putTripAroundEurope();

        $dql = 'SELECT t FROM Doctrine\Tests\Models\Navigation\NavTour t, Doctrine\Tests\Models\Navigation\NavPointOfInterest p ' .
               'WHERE p MEMBER OF t.pois';
        $tours = $this->_em->createQuery($dql)
                           ->getResult();

        $this->assertEquals(1, count($tours));
    }
}