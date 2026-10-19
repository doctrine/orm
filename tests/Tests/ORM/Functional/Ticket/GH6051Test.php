<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-6051')]
class GH6051Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->setUpEntitySchema([GH6051User::class, GH6051SpecialUser::class, GH6051UserInfo::class, GH6051Language::class]);
        $cache = $this->_em->getCache();
        foreach ([GH6051User::class, GH6051UserInfo::class, GH6051Language::class] as $class) {
            $cache->evictEntityRegion($class);
        }

        $cache->getQueryCache($cache->getEntityCacheRegion(GH6051User::class)->getName())->clear();
        $cache->getQueryCache()->clear();
    }

    protected function tearDown(): void
    {
        $connection = $this->_em->getConnection();
        $connection->executeStatement('DELETE FROM GH6051UserInfo');
        $connection->executeStatement('DELETE FROM GH6051SpecialUser');
        $connection->executeStatement('DELETE FROM GH6051User');
        $connection->executeStatement('DELETE FROM GH6051Language');

        parent::tearDown();
    }

    /** @return iterable<string, array{string, bool, bool}> */
    public static function loadingPaths(): iterable
    {
        foreach (['findAll', 'findBy', 'findOneBy', 'query'] as $method) {
            foreach ([false, true] as $withInfo) {
                foreach ([false, true] as $subclass) {
                    yield $method . ($withInfo ? ' with info' : ' without info') . ($subclass ? ' subclass' : ' base')
                        => [$method, $withInfo, $subclass];
                }
            }
        }
    }

    #[DataProvider('loadingPaths')]
    public function testQueryCachePreservesUnselectedToOneAssociations(string $method, bool $withInfo, bool $subclass): void
    {
        $this->persistUser($withInfo, $subclass);

        // Establish the graph returned by SQL hydration without L2 query caching.
        $expected = $this->_em->createQuery('SELECT u, i FROM ' . GH6051User::class . ' u LEFT JOIN u.info i')->getSingleResult();
        self::assertNotNull($expected->actualLanguage);
        self::assertSame('English', $expected->actualLanguage->name);
        self::assertSame($withInfo, $expected->info !== null);
        $this->_em->clear();

        $regionName = $method === 'query'
            ? Cache::DEFAULT_QUERY_REGION_NAME
            : $this->_em->getCache()->getEntityCacheRegion(GH6051User::class)->getName();
        $this->secondLevelCacheLogger->clearStats();

        // A missing inverse one-to-one still creates a relationMap entry,
        // while the query-cache entry has no fetched association identifiers.
        $first = $this->loadUser($method);
        $this->assertAssociations($first, $withInfo, $subclass);
        self::assertSame(1, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
        $this->_em->clear();

        $misses = $this->secondLevelCacheLogger->getRegionMissCount($regionName);
        $hits   = $this->secondLevelCacheLogger->getRegionHitCount($regionName);
        $second = $this->loadUser($method);
        self::assertNotSame($first, $second);
        self::assertSame($misses, $this->secondLevelCacheLogger->getRegionMissCount($regionName));
        self::assertGreaterThan($hits, $this->secondLevelCacheLogger->getRegionHitCount($regionName));
        $this->assertAssociations($second, $withInfo, $subclass);
    }

    public function testMissingFetchedEntryFallsBackToSqlHydration(): void
    {
        $this->persistUser(true, false);
        $this->loadUser('query');
        $this->_em->clear();
        $this->_em->getCache()->evictEntityRegion(GH6051UserInfo::class);

        $this->getQueryLog()->reset()->enable();
        $loaded = $this->loadUser('query');
        $this->assertQueryCount(1);
        $this->assertAssociations($loaded, true, false);
        self::assertSame($loaded, $this->_em->find(GH6051User::class, 1));

        $this->_em->clear();
        $this->assertAssociations($this->loadUser('query'), true, false);
    }

    public function testExistingManagedRootIsReused(): void
    {
        $this->persistUser(true, false);
        $this->loadUser('query');
        $this->_em->clear();

        $existing = $this->_em->createQuery('SELECT u, i FROM ' . GH6051User::class . ' u LEFT JOIN u.info i')->getSingleResult();
        $this->getQueryLog()->reset()->enable();
        $loaded = $this->loadUser('query');
        $this->assertQueryCount(0);
        self::assertSame($existing, $loaded);
        $this->assertAssociations($loaded, true, false);
    }

    private function persistUser(bool $withInfo, bool $subclass): void
    {
        $language             = new GH6051Language();
        $user                 = $subclass ? new GH6051SpecialUser() : new GH6051User();
        $user->actualLanguage = $language;
        $this->_em->persist($language);
        $this->_em->persist($user);

        if ($withInfo) {
            $info       = new GH6051UserInfo($user);
            $user->info = $info;
            $this->_em->persist($info);
        }

        $this->_em->flush();
        $this->_em->clear();
    }

    private function assertAssociations(GH6051User $user, bool $withInfo, bool $subclass): void
    {
        self::assertSame($subclass, $user instanceof GH6051SpecialUser);
        self::assertSame(1, $user->id);
        self::assertNotNull($user->actualLanguage);
        self::assertSame(10, $user->actualLanguage->id);
        self::assertSame('English', $user->actualLanguage->name);
        self::assertSame($this->_em->find(GH6051Language::class, 10), $user->actualLanguage);

        if ($withInfo) {
            self::assertNotNull($user->info);
            self::assertSame(20, $user->info->id);
            self::assertSame($user, $user->info->user);
        } else {
            self::assertNull($user->info);
        }
    }

    private function loadUser(string $method): GH6051User
    {
        $repository = $this->_em->getRepository(GH6051User::class);

        return match ($method) {
            'findAll' => $repository->findAll()[0],
            'findBy' => $repository->findBy(['id' => 1])[0],
            'findOneBy' => $repository->findOneBy(['id' => 1]),
            'query' => $this->_em->createQuery('SELECT u, i FROM ' . GH6051User::class . ' u LEFT JOIN u.info i')
                ->setCacheable(true)->getSingleResult(),
        };
    }
}

#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorMap(['base' => GH6051User::class, 'special' => GH6051SpecialUser::class])]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH6051User
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;

    #[ORM\OneToOne(targetEntity: GH6051UserInfo::class, mappedBy: 'user')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public GH6051UserInfo|null $info = null;

    #[ORM\ManyToOne(targetEntity: GH6051Language::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public GH6051Language|null $actualLanguage = null;
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH6051SpecialUser extends GH6051User
{
    #[ORM\Column(length: 20)]
    public string $name = 'Special user';
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH6051UserInfo
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 20;

    public function __construct(
        #[ORM\OneToOne(targetEntity: GH6051User::class, inversedBy: 'info')]
        #[ORM\JoinColumn(nullable: false)]
        #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
        public GH6051User $user,
    ) {
    }
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH6051Language
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 10;

    #[ORM\Column(length: 20)]
    public string $name = 'English';
}
