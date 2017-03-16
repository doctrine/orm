<?php

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @group DDC-2183
 * @group performance
 */
class SecondLevelCacheTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::useModelSet('cache');
        parent::setUp();
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function createEntityManager()
    {
        $logger = new DebugStack();
        $em     = $this->_getEntityManager();

        $em->getConnection()->getConfiguration()->setSQLLogger($logger);
        $em->getConfiguration()->setSQLLogger($logger);

        return $em;
    }

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @return integer
     */
    public function countQuery(EntityManagerInterface $em)
    {
        return count($em->getConfiguration()->getSQLLogger()->queries);
    }

    public function testFindEntityWithoutCache()
    {
        $em = $this->createEntityManager();

        $this->findEntity($em, __FUNCTION__);

        $this->assertEquals(6002, $this->countQuery($em));
    }

    public function testFindEntityWithCache()
    {
        parent::enableSecondLevelCache(false);

        $em = $this->createEntityManager();

        $this->findEntity($em, __FUNCTION__);

        $this->assertEquals(502, $this->countQuery($em));
    }

    public function testFindAllEntityWithoutCache()
    {
        $em = $this->createEntityManager();

        $this->findAllEntity($em, __FUNCTION__);

        $this->assertEquals(153, $this->countQuery($em));
    }

    public function testFindAllEntityWithCache()
    {
        parent::enableSecondLevelCache(false);

        $em = $this->createEntityManager();

        $this->findAllEntity($em, __FUNCTION__);

        $this->assertEquals(53, $this->countQuery($em));
    }

    public function testFindEntityOneToManyWithoutCache()
    {
        $em = $this->createEntityManager();

        $this->findEntityOneToMany($em, __FUNCTION__);

        $this->assertEquals(502, $this->countQuery($em));
    }

    public function testFindEntityOneToManyWithCache()
    {
        parent::enableSecondLevelCache(false);

        $em = $this->createEntityManager();

        $this->findEntityOneToMany($em, __FUNCTION__);

        $this->assertEquals(472, $this->countQuery($em));
    }

    public function testQueryEntityWithoutCache()
    {
        $em = $this->createEntityManager();

        $this->queryEntity($em, __FUNCTION__);

        $this->assertEquals(602, $this->countQuery($em));
    }

    public function testQueryEntityWithCache()
    {
        parent::enableSecondLevelCache(false);

        $em = $this->createEntityManager();

        $this->queryEntity($em, __FUNCTION__);

        $this->assertEquals(503, $this->countQuery($em));
    }

    private function queryEntity(EntityManagerInterface $em, $label)
    {
        $times        = 100;
        $size         = 500;
        $startPersist = microtime(true);

        echo PHP_EOL . $label;

        for ($i = 0; $i < $size; $i++) {
            $em->persist(new Country("Country $i"));
        }

        $em->flush();
        $em->clear();

        printf("\n[%s] persist %s countries", number_format(microtime(true) - $startPersist, 6), $size);

        $dql        = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c WHERE c.name LIKE :name';
        $startFind  = microtime(true);

        for ($i = 0; $i < $times; $i++) {
            $em->createQuery($dql)
                ->setParameter('name', "%Country%")
                ->setCacheable(true)
                ->getResult();
        }

        printf("\n[%s] select %s countries (%s times)", number_format(microtime(true) - $startFind, 6), $size, $times);
        printf("\n%s\n", str_repeat('-', 50));
    }

    public function findEntityOneToMany(EntityManagerInterface $em, $label)
    {
        $times        = 50;
        $size         = 30;
        $states       = [];
        $cities       = [];
        $startPersist = microtime(true);
        $country      = new Country("Country");

        echo PHP_EOL . $label;

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

        printf("\n[%s] persist %s states and %s cities", number_format( microtime(true) - $startPersist, 6), count($states), count($cities));

        $startFind  = microtime(true);

        for ($i = 0; $i < $times; $i++) {

            foreach ($states as $state) {

                $state = $em->find(State::class, $state->getId());

                foreach ($state->getCities() as $city) {
                    $city->getName();
                }
            }
        }

        printf("\n[%s] find %s states and %s cities (%s times)", number_format(microtime(true) - $startFind, 6), count($states), count($cities), $times);
        printf("\n%s\n", str_repeat('-', 50));
    }

    private function findEntity(EntityManagerInterface $em, $label)
    {
        $times        = 10;
        $size         = 500;
        $countries    = [];
        $startPersist = microtime(true);

        echo PHP_EOL . $label;

        for ($i = 0; $i < $size; $i++) {
            $country = new Country("Country $i");

            $em->persist($country);

            $countries[] = $country;
        }

        $em->flush();
        $em->clear();

        printf("\n[%s] persist %s countries", number_format(microtime(true) - $startPersist, 6), $size);

        $startFind  = microtime(true);

        for ($i = 0; $i <= $times; $i++) {
            foreach ($countries as $country) {
                $em->find(Country::class, $country->getId());
                $em->clear();
            }
        }

        printf("\n[%s] find %s countries (%s times)", number_format(microtime(true) - $startFind, 6), $size, $times);
        printf("\n%s\n", str_repeat('-', 50));
    }

    private function findAllEntity(EntityManagerInterface $em, $label)
    {
        $times        = 100;
        $size         = 50;
        $startPersist = microtime(true);
        $rep          = $em->getRepository(Country::class);

        echo PHP_EOL . $label;

        for ($i = 0; $i < $size; $i++) {
            $em->persist(new Country("Country $i"));
        }

        $em->flush();
        $em->clear();

        printf("\n[%s] persist %s countries", number_format(microtime(true) - $startPersist, 6), $size);

        $startFind  = microtime(true);

        for ($i = 0; $i <= $times; $i++) {
            $list = $rep->findAll();
            $em->clear();

            $this->assertCount($size, $list);
        }

        printf("\n[%s] find %s countries (%s times)", number_format(microtime(true) - $startFind, 6), $size, $times);
        printf("\n%s\n", str_repeat('-', 50));
    }
}
