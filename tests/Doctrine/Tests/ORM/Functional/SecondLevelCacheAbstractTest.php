<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\OrmFunctionalTestCase;

use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\City;

use Doctrine\Tests\Models\Cache\Traveler;
use Doctrine\Tests\Models\Cache\Travel;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 */
abstract class SecondLevelCacheAbstractTest extends OrmFunctionalTestCase
{
    protected $countries = array();
    protected $states    = array();
    protected $cities    = array();
    protected $travels   = array();
    protected $travelers = array();

    /**
     * @var \Doctrine\ORM\Cache
     */
    protected $cache;

    protected function setUp()
    {
        $this->enableSecondLevelCache();

        $this->useModelSet('cache');

        parent::setUp();

        $this->cache = $this->_em->getCache();
    }

    protected function loadFixturesCountries()
    {
        $brazil  = new Country("Brazil");
        $germany = new Country("Germany");

        $this->countries[] = $brazil;
        $this->countries[] = $germany;

        $this->_em->persist($brazil);
        $this->_em->persist($germany);
        $this->_em->flush();
    }

    protected function loadFixturesStates()
    {
        $saopaulo   = new State("São Paulo", $this->countries[0]);
        $rio        = new State("Rio de janeiro", $this->countries[0]);
        $berlin     = new State("Berlin", $this->countries[1]);
        $bavaria    = new State("Bavaria", $this->countries[1]);

        $this->states[] = $saopaulo;
        $this->states[] = $rio;
        $this->states[] = $bavaria;
        $this->states[] = $berlin;

        $this->_em->persist($saopaulo);
        $this->_em->persist($rio);
        $this->_em->persist($bavaria);
        $this->_em->persist($berlin);

        $this->_em->flush();
    }

    protected function loadFixturesCities()
    {
        $saopaulo   = new City("São Paulo", $this->states[0]);
        $rio        = new City("Rio de janeiro", $this->states[0]);
        $berlin     = new City("Berlin", $this->states[1]);
        $munich     = new City("Munich", $this->states[1]);

        $this->states[0]->addCity($saopaulo);
        $this->states[0]->addCity($rio);
        $this->states[1]->addCity($berlin);
        $this->states[1]->addCity($berlin);

        $this->cities[] = $saopaulo;
        $this->cities[] = $rio;
        $this->cities[] = $munich;
        $this->cities[] = $berlin;

        $this->_em->persist($saopaulo);
        $this->_em->persist($rio);
        $this->_em->persist($munich);
        $this->_em->persist($berlin);

        $this->_em->flush();
    }

    protected function loadFixturesTraveler()
    {
        $t1   = new Traveler("Fabio Silva");
        $t2   = new Traveler("Doctrine Bot");

        $this->_em->persist($t1);
        $this->_em->persist($t2);

        $this->travelers[] = $t1;
        $this->travelers[] = $t2;

        $this->_em->flush();
    }

    protected function loadFixturesTravels()
    {
        $t1   = new Travel($this->travelers[0]);
        $t2   = new Travel($this->travelers[1]);

        $t1->addVisitedCity($this->cities[0]);
        $t1->addVisitedCity($this->cities[1]);
        $t1->addVisitedCity($this->cities[2]);

        $t2->addVisitedCity($this->cities[1]);
        $t2->addVisitedCity($this->cities[3]);

        $this->_em->persist($t1);
        $this->_em->persist($t2);

        $this->travels[] = $t1;
        $this->travels[] = $t2;

        $this->_em->flush();
    }
}