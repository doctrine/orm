<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Cache;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\Tests\Models\Cache\Traveler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function ksort;

#[Group('GH-7277')]
class SecondLevelCacheNestedFetchJoinTest extends SecondLevelCacheFunctionalTestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function fetchJoinPaths(): iterable
    {
        yield 'to-one after two collections' => ['s', 'LEFT JOIN c.state s', 'state'];
        yield 'nullable intermediate to-one' => ['s, n', 'LEFT JOIN c.state s LEFT JOIN s.country n', 'country'];
        yield 'three collections' => ['a', 'LEFT JOIN c.attractions a', 'attractions'];
        yield 'four collections' => ['a, i', 'LEFT JOIN c.attractions a LEFT JOIN a.infos i', 'infos'];
    }

    #[DataProvider('fetchJoinPaths')]
    public function testNestedFetchJoins(string $select, string $joins, string $leaf): void
    {
        $this->loadFixturesCountries();
        $this->loadFixturesStates();
        $this->loadFixturesCities();
        $this->loadFixturesTraveler();
        $this->loadFixturesTravels();
        $this->loadFixturesAttractions();
        $this->loadFixturesAttractionsInfo();

        // Exercise a null to-one, an empty terminal collection, and a sibling
        // travel with no cities (provided by loadFixturesTravels()).
        $city   = new City('City without a state');
        $travel = new Travel($this->travelers[0]);
        $travel->addVisitedCity($city);
        $this->_em->persist($travel);
        $this->_em->flush();
        $this->_em->clear();

        $dql = 'SELECT t, r, c, ' . $select . ' FROM ' . Traveler::class . ' t'
            . ' LEFT JOIN t.travels r LEFT JOIN r.visitedCities c ' . $joins
            . ' ORDER BY t.id, r.id, c.id';

        $expected = $this->snapshot($this->_em->createQuery($dql)->getResult(), $leaf);
        self::assertCount(2, $expected);
        $this->_em->clear();
        $this->evictRegions();
        $this->secondLevelCacheLogger->clearStats();

        $query = $this->_em->createQuery($dql)->setCacheable(true);
        $this->getQueryLog()->reset()->enable();
        self::assertSame($expected, $this->snapshot($query->getResult(), $leaf));
        $this->assertQueryCount(1);
        self::assertSame(1, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));

        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();
        $result = $query->getResult();
        $this->assertQueryCount(0);
        self::assertSame(1, $this->secondLevelCacheLogger->getRegionHitCount($this->getDefaultQueryRegionName()));
        self::assertSame($expected, $this->snapshot($result, $leaf));

        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();
        self::assertSame($expected, $this->snapshot($query->setCacheMode(Cache::MODE_REFRESH)->getResult(), $leaf));
        $this->assertQueryCount(1);
        self::assertSame(2, $this->secondLevelCacheLogger->getRegionPutCount($this->getDefaultQueryRegionName()));
    }

    /**
     * @param list<Traveler> $travelers
     *
     * @return array<int, mixed>
     */
    private function snapshot(array $travelers, string $leaf): array
    {
        $result = [];
        foreach ($travelers as $traveler) {
            $travels = [];
            foreach ($traveler->getTravels() as $travel) {
                $cities = [];
                foreach ($travel->getVisitedCities() as $city) {
                    $value = match ($leaf) {
                        'state' => $city->getState()?->getName(),
                        'country' => $city->getState()?->getCountry()?->getName(),
                        default => $this->snapshotAttractions($city, $leaf),
                    };
                    $cities[$city->getId()] = [$city->getName(), $value];
                }

                $travels[$travel->getId()] = $cities;
            }

            $result[$traveler->getId()] = $travels;
        }

        return $result;
    }

    /** @return array<int, mixed> */
    private function snapshotAttractions(City $city, string $leaf): array
    {
        $result = [];
        foreach ($city->getAttractions() as $attraction) {
            $infos = [];
            if ($leaf === 'infos') {
                foreach ($attraction->getInfos() as $info) {
                    $infos[$info->getId()] = $info->getAttraction()->getId();
                }
            }

            ksort($infos);
            $result[$attraction->getId()] = [$attraction->getName(), $infos];
        }

        ksort($result);

        return $result;
    }
}
