<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function count;
use function microtime;
use function number_format;
use function printf;
use function sprintf;
use function str_repeat;

use const PHP_EOL;

#[Group('DDC-2183')]
#[Group('performance')]
class SecondLevelCacheTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::useModelSet('cache');

        parent::setUp();
    }

    public function testFindEntityWithoutCache(): void
    {
        $this->getQueryLog()->reset()->enable();
        $this->findEntity($this->_em, __FUNCTION__);

        $this->assertQueryCount(6002);
    }

    public function testFindEntityWithCache(): void
    {
        parent::enableSecondLevelCache(false);

        $this->getQueryLog()->reset()->enable();
        $this->findEntity($this->_em, __FUNCTION__);

        $this->assertQueryCount(502);
    }

    public function testFindAllEntityWithoutCache(): void
    {
        $this->getQueryLog()->reset()->enable();
        $this->findAllEntity($this->_em, __FUNCTION__);

        $this->assertQueryCount(153);
    }

    public function testFindAllEntityWithCache(): void
    {
        parent::enableSecondLevelCache(false);

        $this->getQueryLog()->reset()->enable();
        $this->findAllEntity($this->_em, __FUNCTION__);

        $this->assertQueryCount(53);
    }

    public function testFindEntityOneToManyWithoutCache(): void
    {
        $this->getQueryLog()->reset()->enable();
        $this->findEntityOneToMany($this->_em, __FUNCTION__);

        $this->assertQueryCount(502);
    }

    public function testFindEntityOneToManyWithCache(): void
    {
        parent::enableSecondLevelCache(false);

        $this->getQueryLog()->reset()->enable();
        $this->findEntityOneToMany($this->_em, __FUNCTION__);

        $this->assertQueryCount(472);
    }

    public function testQueryEntityWithoutCache(): void
    {
        $this->getQueryLog()->reset()->enable();
        $this->queryEntity($this->_em, __FUNCTION__);

        $this->assertQueryCount(602);
    }

    public function testQueryEntityWithCache(): void
    {
        parent::enableSecondLevelCache(false);

        $this->getQueryLog()->reset()->enable();
        $this->queryEntity($this->_em, __FUNCTION__);

        $this->assertQueryCount(503);
    }

    private function queryEntity(EntityManagerInterface $em, string $label): void
    {
        $times        = 100;
        $size         = 500;
        $startPersist = microtime(true);

        echo PHP_EOL . $label;

        for ($i = 0; $i < $size; $i++) {
            $em->persist(new Country(sprintf('Country %d', $i)));
        }

        $em->flush();
        $em->clear();

        printf("\n[%s] persist %s countries", number_format(microtime(true) - $startPersist, 6), $size);

        $dql       = 'SELECT c FROM Doctrine\Tests\Models\Cache\Country c WHERE c.name LIKE :name';
        $startFind = microtime(true);

        for ($i = 0; $i < $times; $i++) {
            $em->createQuery($dql)
                ->setParameter('name', '%Country%')
                ->setCacheable(true)
                ->getResult();
        }

        printf("\n[%s] select %s countries (%s times)", number_format(microtime(true) - $startFind, 6), $size, $times);
        printf("\n%s\n", str_repeat('-', 50));
    }

    public function findEntityOneToMany(EntityManagerInterface $em, string $label): void
    {
        $times        = 50;
        $size         = 30;
        $states       = [];
        $cities       = [];
        $startPersist = microtime(true);
        $country      = new Country('Country');

        echo PHP_EOL . $label;

        $em->persist($country);
        $em->flush();

        for ($i = 0; $i < $size / 2; $i++) {
            $state = new State(sprintf('State %d', $i), $country);

            $em->persist($state);

            $states[] = $state;
        }

        $em->flush();

        foreach ($states as $key => $state) {
            for ($i = 0; $i < $size; $i++) {
                $city = new City(sprintf('City %s - %d', $key, $i), $state);

                $em->persist($city);

                $state->addCity($city);

                $cities[] = $city;
            }
        }

        $em->flush();
        $em->clear();

        printf("\n[%s] persist %s states and %s cities", number_format(microtime(true) - $startPersist, 6), count($states), count($cities));

        $startFind = microtime(true);

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

    private function findEntity(EntityManagerInterface $em, $label): void
    {
        $times        = 10;
        $size         = 500;
        $countries    = [];
        $startPersist = microtime(true);

        echo PHP_EOL . $label;

        for ($i = 0; $i < $size; $i++) {
            $country = new Country(sprintf('Country %d', $i));

            $em->persist($country);

            $countries[] = $country;
        }

        $em->flush();
        $em->clear();

        printf("\n[%s] persist %s countries", number_format(microtime(true) - $startPersist, 6), $size);

        $startFind = microtime(true);

        for ($i = 0; $i <= $times; $i++) {
            foreach ($countries as $country) {
                $em->find(Country::class, $country->getId());
                $em->clear();
            }
        }

        printf("\n[%s] find %s countries (%s times)", number_format(microtime(true) - $startFind, 6), $size, $times);
        printf("\n%s\n", str_repeat('-', 50));
    }

    private function findAllEntity(EntityManagerInterface $em, string $label): void
    {
        $times        = 100;
        $size         = 50;
        $startPersist = microtime(true);
        $rep          = $em->getRepository(Country::class);

        echo PHP_EOL . $label;

        for ($i = 0; $i < $size; $i++) {
            $em->persist(new Country(sprintf('Country %d', $i)));
        }

        $em->flush();
        $em->clear();

        printf("\n[%s] persist %s countries", number_format(microtime(true) - $startPersist, 6), $size);

        $startFind = microtime(true);

        for ($i = 0; $i <= $times; $i++) {
            $list = $rep->findAll();
            $em->clear();

            self::assertCount($size, $list);
        }

        printf("\n[%s] find %s countries (%s times)", number_format(microtime(true) - $startFind, 6), $size, $times);
        printf("\n%s\n", str_repeat('-', 50));
    }
}
