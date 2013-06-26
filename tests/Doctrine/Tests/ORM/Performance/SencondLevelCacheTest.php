<?php

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\City;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 * @group performance
 */
class SencondLevelCacheTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::useModelSet('cache');
        parent::setUp();
    }

    public function testFindEntityWithoutCache()
    {
        $this->findEntity(__FUNCTION__);
    }

    public function testFindEntityWithCache()
    {
        parent::enableSecondLevelCache(false);

        $this->findEntity(__FUNCTION__);
    }

    public function testFindEntityOneToManyWithoutCache()
    {

        $this->findEntityOneToMany(__FUNCTION__);
    }

    public function testFindEntityOneToManyWithCache()
    {
        parent::enableSecondLevelCache(false);

        $this->findEntityOneToMany(__FUNCTION__);
    }

    public function testQueryEntityWithoutCache()
    {
        $this->queryEntity(__FUNCTION__);
    }

    public function testQueryEntityWithCache()
    {
        parent::enableSecondLevelCache(false);

        $this->queryEntity(__FUNCTION__);
    }

    private function queryEntity($label)
    {
        $times          = 500;
        $size           = 500;
        $startPersist   = microtime(true);
        $em             = $this->_getEntityManager();

        for ($i = 0; $i < $size; $i++) {
            $em->persist(new Country("Country $i"));
        }

        $em->flush();
        $em->close();

        printf("\n$label - [%s] persist %s countries\n", number_format(microtime(true) - $startPersist, 6), $size);

        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c WHERE c.name = :name OR c.id < :count';
        $startFind  = microtime(true);

        for ($i = 0; $i < $times; $i++) {
            $em = $this->_getEntityManager();

            $em->createQuery($dql)
                ->setParameter('name', "Country $i")
                ->setParameter('count', $i)
                ->setCacheable(true)
                ->getResult();
        }

        printf("$label - [%s] select %s countries (%s times)\n", number_format(microtime(true) - $startFind, 6), $size, $times);
    }

    public function findEntityOneToMany($label)
    {
        $times        = 50;
        $size         = 30;
        $states       = array();
        $cities       = array();
        $startPersist = microtime(true);
        $em           = $this->_getEntityManager();
        $country      = new Country("Country");

        $em->persist($country);
        $em->flush();

        for ($i = 0; $i < $size / 2; $i++) {
            $state = new State("State $i", $country);

            $em->persist($state);

            $states[] = $state;
        }

        $em->flush();

        foreach ($states as $key => $state) {
            for ($i = 0; $i < $size; $i++) {
                $city = new City("City $key - $i", $state);

                $em->persist($city);

                $state->addCity($city);

                $cities[] = $city;
            }
        }

        $em->flush();
        $em->clear();

        $endPersist = microtime(true);
        $format     = "\n$label - [%s] persist %s states and %s cities\n";

        printf($format, number_format($endPersist - $startPersist, 6), count($states), count($cities));

        $startFind  = microtime(true);

        for ($i = 0; $i < $times; $i++) {

            $em = $this->_getEntityManager();

            foreach ($states as $state) {

                $state = $em->find(State::CLASSNAME, $state->getId());

                foreach ($state->getCities() as $city) {
                    $city->getName();
                }
            }
        }

        $endFind = microtime(true);
        $format  = "$label - [%s] find %s states and %s cities (%s times)\n";

        printf($format, number_format($endFind - $startFind, 6), count($states), count($cities), $times);
    }

    private function findEntity($label)
    {
        $times        = 50;
        $size         = 500;
        $countries    = array();
        $startPersist = microtime(true);
        $em           = $this->_getEntityManager();

        for ($i = 0; $i < $size; $i++) {
            $country = new Country("Country $i");

            $em->persist($country);

            $countries[] = $country;
        }

        $em->flush();
        $em->close();

        printf("\n$label - [%s] persist %s countries \n", number_format(microtime(true) - $startPersist, 6), $size);

        $startFind  = microtime(true);

        for ($i = 0; $i < $times; $i++) {
            $em = $this->_getEntityManager();

            foreach ($countries as $country) {
                $em->find(Country::CLASSNAME, $country->getId());
            }
        }

        printf("$label - [%s] find %s countries (%s times)\n", number_format(microtime(true) - $startFind, 6), $size, $times);
    }
}