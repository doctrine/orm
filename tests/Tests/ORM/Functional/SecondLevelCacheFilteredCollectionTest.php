<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Cache\CollectionCacheKey;
use Doctrine\ORM\Cache\DefaultCacheFactory;
use Doctrine\ORM\Cache\Region\FileLockRegion;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\Tests\Models\Cache\City;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\Models\Cache\Travel;
use Doctrine\Tests\Models\Cache\Traveler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_filter;
use function array_map;
use function array_values;
use function assert;
use function count;
use function is_dir;
use function rmdir;
use function sort;
use function str_starts_with;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[Group('DDC-2183')]
class SecondLevelCacheFilteredCollectionTest extends SecondLevelCacheFunctionalTestCase
{
    private ClassMetadata $travelMetadata;
    private ClassMetadata $stateMetadata;
    private string|null $lockDirectory = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelMetadata = $this->_em->getClassMetadata(Travel::class);
        $this->stateMetadata  = $this->_em->getClassMetadata(State::class);
        $this->_em->getConfiguration()->addFilter('collection_name', CollectionCityNameFilter::class);
    }

    protected function tearDown(): void
    {
        $this->setFilter(null);

        parent::tearDown();

        $this->_em->getMetadataFactory()->setMetadataFor(Travel::class, $this->travelMetadata);
        $this->_em->getMetadataFactory()->setMetadataFor(State::class, $this->stateMetadata);
        if ($this->lockDirectory !== null && is_dir($this->lockDirectory)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->lockDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }

            rmdir($this->lockDirectory);
        }
    }

    #[DataProvider('changes')]
    public function testChangesInvalidateAllFilterVariants(int $usage, string|null $writeFilter, string $change): void
    {
        $this->configureCollection($usage);
        [$owner, $neighbor] = $this->createFixtures();
        $this->warmVariants($owner, $neighbor);
        $collection = $this->readCollection($owner, $writeFilter);
        $collection->toArray();

        if ($change === 'append') {
            $travel = $this->_em->find(Travel::class, $owner);
            $city   = new City('visible-a2');
            $travel->addVisitedCity($city);
            $city->addTravel($travel);
            $this->_em->persist($city);
            $expected = ['other-a1', 'visible-a1', 'visible-a2'];
        } elseif ($change === 'remove') {
            foreach ($collection as $city) {
                if ($city->getName() === 'visible-a1') {
                    $collection->removeElement($city);
                }
            }

            $expected = ['other-a1'];
        } else {
            $collection->clear();
            $expected = [];
        }

        $this->_em->flush();
        $this->assertVariants($owner, $expected);
        $this->assertNeighborRemainsCached($neighbor);
    }

    /** @return iterable<string, array{int, string|null, string}> */
    public static function changes(): iterable
    {
        foreach (self::strategies() as $strategy => [$usage]) {
            foreach (['unfiltered' => null, 'filtered' => 'visible'] as $context => $filter) {
                foreach (['append', 'remove', 'clear'] as $change) {
                    yield $strategy . ' ' . $context . ' ' . $change => [$usage, $filter, $change];
                }
            }
        }
    }

    #[DataProvider('strategies')]
    public function testDirtyUninitializedCollectionInvalidatesAllVariants(int $usage): void
    {
        $this->configureCollection($usage);
        [$owner, $neighbor] = $this->createFixtures();
        $this->warmVariants($owner, $neighbor);
        $collection = $this->readCollection($owner, 'visible');
        $city       = new City('visible-a2');
        $city->addTravel($this->_em->find(Travel::class, $owner));
        $collection->add($city);
        $this->_em->persist($city);
        $this->_em->flush();

        $this->assertVariants($owner, ['other-a1', 'visible-a1', 'visible-a2']);
        $this->assertNeighborRemainsCached($neighbor);
    }

    #[DataProvider('strategies')]
    public function testNewElementsOutsideTheFilterAreNotPublishedInFilteredVariant(int $usage): void
    {
        $this->configureCollection($usage);
        [$owner, $neighbor] = $this->createFixtures();
        $this->warmVariants($owner, $neighbor);
        $collection = $this->readCollection($owner, 'visible');
        $collection->toArray();
        $travel = $this->_em->find(Travel::class, $owner);
        $city   = new City('other-a2');
        $city->addTravel($travel);
        $travel->addVisitedCity($city);
        $this->_em->persist($city);
        $this->_em->flush();

        $this->assertVariants($owner, ['other-a1', 'other-a2', 'visible-a1']);
        $this->assertNeighborRemainsCached($neighbor);
    }

    #[DataProvider('strategies')]
    public function testFilteredSnapshotIsNotPublishedAfterDisablingFilter(int $usage): void
    {
        $this->configureCollection($usage);
        [$owner, $neighbor] = $this->createFixtures();
        $this->warmVariants($owner, $neighbor);
        $collection = $this->readCollection($owner, 'visible');
        $collection->toArray();
        $this->setFilter(null);
        $travel = $this->_em->find(Travel::class, $owner);
        $city   = new City('visible-a2');
        $city->addTravel($travel);
        $travel->addVisitedCity($city);
        $this->_em->persist($city);
        $this->_em->flush();

        $this->assertVariants($owner, ['other-a1', 'visible-a1', 'visible-a2']);
        $this->assertNeighborRemainsCached($neighbor);
    }

    #[DataProvider('strategies')]
    public function testExplicitEvictionInvalidatesAllFilterVariants(int $usage): void
    {
        $this->configureCollection($usage);
        [$owner, $neighbor] = $this->createFixtures();
        $this->warmVariants($owner, $neighbor);
        $this->setFilter('visible');
        $this->cache->evictCollection(Travel::class, 'visitedCities', $owner);

        foreach ([null, 'visible', 'other'] as $filter) {
            $collection = $this->readCollection($owner, $filter);
            self::assertFalse($this->cache->containsCollection(Travel::class, 'visitedCities', $owner));
            $this->getQueryLog()->reset()->enable();
            $collection->toArray();
            $this->assertQueryCount(1);
        }

        $this->assertNeighborRemainsCached($neighbor);
    }

    public function testReadWriteLockCoversAllFilterVariants(): void
    {
        $this->configureCollection(ClassMetadata::CACHE_USAGE_READ_WRITE);
        [$owner, $neighbor] = $this->createFixtures();
        $this->warmVariants($owner, $neighbor);
        $region = $this->cache->getCollectionCacheRegion(Travel::class, 'visitedCities');
        self::assertInstanceOf(FileLockRegion::class, $region);
        $key  = new CollectionCacheKey(Travel::class, 'visitedCities', ['id' => $owner]);
        $lock = $region->lock($key);
        self::assertNotNull($lock);
        try {
            foreach ([null, 'visible', 'other'] as $filter) {
                $collection = $this->readCollection($owner, $filter);
                self::assertFalse($this->cache->containsCollection(Travel::class, 'visitedCities', $owner));
                $this->getQueryLog()->reset()->enable();
                $collection->toArray();
                $this->assertQueryCount(1);
            }
        } finally {
            $region->unlock($key, $lock);
        }

        $this->assertNeighborRemainsCached($neighbor);
    }

    #[DataProvider('strategies')]
    public function testOneToManyUpdateInvalidatesAllVariants(int $usage): void
    {
        $this->configureCollection($usage);
        $state = new State('filter-state');
        $this->_em->persist($state);
        foreach (['visible-state1', 'other-state1'] as $name) {
            $this->_em->persist(new City($name, $state));
        }

        $this->_em->flush();
        foreach ([null, 'visible', 'other'] as $filter) {
            $this->setFilter($filter);
            $this->_em->clear();
            $this->_em->find(State::class, $state->getId())->getCities()->toArray();
        }

        $this->setFilter('visible');
        $this->_em->clear();
        $state = $this->_em->find(State::class, $state->getId());
        $state->getCities()->toArray();
        $city = new City('visible-state2', $state);
        $state->addCity($city);
        $this->_em->persist($city);
        $this->_em->flush();

        foreach (['all' => 3, 'visible' => 2, 'other' => 1] as $filter => $expected) {
            $this->setFilter($filter === 'all' ? null : $filter);
            $this->_em->clear();
            $collection = $this->_em->find(State::class, $state->getId())->getCities();
            self::assertCount($expected, $collection);
            self::assertCount($expected, $collection->toArray());
        }
    }

    /** @return iterable<string, array{int}> */
    public static function strategies(): iterable
    {
        yield 'NONSTRICT_READ_WRITE' => [ClassMetadata::CACHE_USAGE_NONSTRICT_READ_WRITE];
        yield 'READ_WRITE' => [ClassMetadata::CACHE_USAGE_READ_WRITE];
    }

    private function configureCollection(int $usage): void
    {
        $metadata                                       = clone $this->travelMetadata;
        $mapping                                        = clone $metadata->associationMappings['visitedCities'];
        $mapping->cache['usage']                        = $usage;
        $mapping->fetch                                 = ClassMetadata::FETCH_EXTRA_LAZY;
        $metadata->associationMappings['visitedCities'] = $mapping;
        $this->_em->getMetadataFactory()->setMetadataFor(Travel::class, $metadata);
        $stateMetadata                                = clone $this->stateMetadata;
        $stateMapping                                 = clone $stateMetadata->associationMappings['cities'];
        $stateMapping->cache['usage']                 = $usage;
        $stateMapping->fetch                          = ClassMetadata::FETCH_EXTRA_LAZY;
        $stateMetadata->associationMappings['cities'] = $stateMapping;
        $this->_em->getMetadataFactory()->setMetadataFor(State::class, $stateMetadata);
        if ($usage === ClassMetadata::CACHE_USAGE_READ_WRITE) {
            $this->lockDirectory = sys_get_temp_dir() . '/doctrine_collection_filters_' . uniqid();
            $factory             = $this->_em->getConfiguration()->getSecondLevelCacheConfiguration()->getCacheFactory();
            assert($factory instanceof DefaultCacheFactory);
            $factory->setFileLockRegionDirectory($this->lockDirectory);
        }
    }

    /** @return array{int, int} */
    private function createFixtures(): array
    {
        $traveler = new Traveler('filter test');
        $first    = new Travel($traveler);
        $second   = new Travel($traveler);
        $this->_em->persist($traveler);
        foreach (['a' => $first, 'b' => $second] as $suffix => $travel) {
            $this->_em->persist($travel);
            foreach (['visible', 'other'] as $prefix) {
                $city = new City($prefix . '-' . $suffix . '1');
                $travel->addVisitedCity($city);
                $city->addTravel($travel);
                $this->_em->persist($city);
            }
        }

        $this->_em->flush();
        $this->_em->clear();

        return [$first->getId(), $second->getId()];
    }

    private function warmVariants(int $owner, int $neighbor): void
    {
        $this->assertVariants($owner, ['other-a1', 'visible-a1']);
        $this->assertVariants($neighbor, ['other-b1', 'visible-b1']);
        $this->assertNeighborRemainsCached($neighbor);
    }

    /** @param list<string> $expected */
    private function assertVariants(int $owner, array $expected): void
    {
        foreach ([null, 'visible', 'other'] as $filter) {
            $names      = $filter === null ? $expected : array_values(array_filter(
                $expected,
                static fn (string $name): bool => str_starts_with($name, $filter),
            ));
            $collection = $this->readCollection($owner, $filter);
            self::assertCount(count($names), $collection);
            $actual = array_map(static fn (City $city): string => $city->getName(), $collection->toArray());
            sort($actual);
            self::assertSame($names, $actual);
            self::assertTrue($this->cache->containsCollection(Travel::class, 'visitedCities', $owner));
        }
    }

    private function assertNeighborRemainsCached(int $neighbor): void
    {
        foreach ([null, 'visible', 'other'] as $filter) {
            $collection = $this->readCollection($neighbor, $filter);
            $this->getQueryLog()->reset()->enable();
            $collection->toArray();
            $this->assertQueryCount(0);
        }
    }

    /** @return Collection<int, City> */
    private function readCollection(int $owner, string|null $filter): Collection
    {
        $this->setFilter($filter);
        $this->_em->clear();

        return $this->_em->find(Travel::class, $owner)->getVisitedCities();
    }

    private function setFilter(string|null $prefix): void
    {
        $filters = $this->_em->getFilters();
        if ($prefix !== null) {
            $filters->enable('collection_name')->setParameter('prefix', $prefix . '%');
        } elseif ($filters->isEnabled('collection_name')) {
            $filters->disable('collection_name');
        }
    }
}

class CollectionCityNameFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        return $targetEntity->name === City::class
            ? $targetTableAlias . '.name LIKE ' . $this->getParameter('prefix')
            : '';
    }
}
