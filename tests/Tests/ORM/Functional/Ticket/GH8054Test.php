<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Cache;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-8054')]
class GH8054Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->setUpEntitySchema([GH8054User::class, GH8054Schedule::class, GH8054AssignedSchedule::class]);
        foreach ([GH8054User::class, GH8054Schedule::class, GH8054AssignedSchedule::class] as $class) {
            $this->_em->getCache()->evictEntityRegion($class);
        }

        $cache = $this->_em->getCache();
        $cache->getQueryCache($cache->getEntityCacheRegion(GH8054User::class)->getName())->clear();
        $cache->getQueryCache()->clear();
        $cache->evictCollectionRegion(GH8054User::class, 'schedules');
    }

    protected function tearDown(): void
    {
        $connection = $this->_em->getConnection();
        $connection->executeStatement('DELETE FROM GH8054AssignedSchedule');
        $connection->executeStatement('DELETE FROM GH8054Schedule');
        $connection->executeStatement('DELETE FROM GH8054User');

        parent::tearDown();
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function queriesBeforeFlush(): iterable
    {
        foreach (['schedule', 'assignedSchedule'] as $association) {
            foreach (['findAll', 'findBy', 'findOneBy', 'query', 'refresh'] as $method) {
                foreach ([false, true] as $persist) {
                    yield $association . ' ' . $method . ($persist ? ' pending insert' : ' new') => [$association, $method, $persist];
                }
            }
        }

        yield 'collection new' => ['schedules', 'query', false];
        yield 'collection pending insert' => ['schedules', 'query', true];
    }

    #[DataProvider('queriesBeforeFlush')]
    public function testQueryDoesNotCacheAnUnflushedAssociation(string $association, string $method, bool $persist): void
    {
        $user = new GH8054User();
        $this->_em->persist($user);
        $this->_em->flush();

        $schedule = $association === 'assignedSchedule' ? new GH8054AssignedSchedule($user) : new GH8054Schedule();
        if ($association === 'schedules') {
            $schedule->owner = $user;
            $user->schedules->add($schedule);
        } else {
            $schedule->user       = $user;
            $user->{$association} = $schedule;
        }

        if ($persist) {
            $this->_em->persist($schedule);
        }

        // Sequence generators can assign an ID during persist(), before INSERT.
        $identifierBeforeQuery = $schedule->id;

        // Inverse one-to-one mappings are valid even though the new target
        // has not been inserted yet, as during UniqueEntity validation.
        $validator = new SchemaValidator($this->_em);
        self::assertSame([], $validator->validateClass($this->_em->getClassMetadata(GH8054User::class)));

        $regionName = $method === 'query' || $method === 'refresh'
            ? Cache::DEFAULT_QUERY_REGION_NAME
            : $this->_em->getCache()->getEntityCacheRegion(GH8054User::class)->getName();
        $this->secondLevelCacheLogger->clearStats();

        for ($i = 0; $i < 2; ++$i) {
            $this->getQueryLog()->reset()->enable();
            self::assertSame($user, $this->loadUser($association, $method));
            $this->assertQueryCount(1);
        }

        self::assertSame(0, $this->secondLevelCacheLogger->getRegionPutCount($regionName));
        self::assertSame($schedule, $association === 'schedules' ? $user->schedules->first() : $user->{$association});
        self::assertSame(0, (int) $this->_em->getConnection()->fetchOne('SELECT COUNT(*) FROM GH8054Schedule'));
        self::assertSame(0, (int) $this->_em->getConnection()->fetchOne('SELECT COUNT(*) FROM GH8054AssignedSchedule'));
        self::assertSame($identifierBeforeQuery, $schedule->id);
        if ($identifierBeforeQuery !== null) {
            self::assertFalse($this->_em->getCache()->containsEntity($schedule::class, $identifierBeforeQuery));
        }

        $this->_em->flush();
        self::assertNotNull($schedule->id);
        $this->_em->clear();

        $loaded = $this->loadUser($association, $method === 'refresh' ? 'query' : $method);
        $target = $association === 'schedules' ? $loaded->schedules->first() : $loaded->{$association};
        self::assertSame($schedule->id, $target->id);

        $this->_em->clear();
        $this->getQueryLog()->reset()->enable();
        $missCount = $this->secondLevelCacheLogger->getRegionMissCount($regionName);
        $loaded    = $this->loadUser($association, $method === 'refresh' ? 'query' : $method);
        self::assertSame($missCount, $this->secondLevelCacheLogger->getRegionMissCount($regionName));
        $target = $association === 'schedules' ? $loaded->schedules->first() : $loaded->{$association};
        self::assertSame($schedule->id, $target->id);
    }

    private function loadUser(string $association, string $method): GH8054User
    {
        $repository = $this->_em->getRepository(GH8054User::class);

        return match ($method) {
            'findAll' => $repository->findAll()[0],
            'findBy' => $repository->findBy(['email' => 'user@example.com'])[0],
            'findOneBy' => $repository->findOneBy(['email' => 'user@example.com']),
            default => $this->_em->createQuery('SELECT u, s FROM ' . GH8054User::class . ' u LEFT JOIN u.' . $association . ' s')
                ->setCacheable(true)->setCacheMode($method === 'refresh' ? Cache::MODE_REFRESH : Cache::MODE_NORMAL)->getSingleResult(),
        };
    }
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH8054User
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;

    #[ORM\Column(length: 100, unique: true)]
    public string $email = 'user@example.com';

    #[ORM\OneToOne(targetEntity: GH8054Schedule::class, mappedBy: 'user', cascade: ['persist'])]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public GH8054Schedule|null $schedule = null;

    #[ORM\OneToOne(targetEntity: GH8054AssignedSchedule::class, mappedBy: 'user', cascade: ['persist'])]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public GH8054AssignedSchedule|null $assignedSchedule = null;

    /** @var Collection<int, GH8054Schedule> */
    #[ORM\OneToMany(targetEntity: GH8054Schedule::class, mappedBy: 'owner', cascade: ['persist'])]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public Collection $schedules;

    public function __construct()
    {
        $this->schedules = new ArrayCollection();
    }
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH8054Schedule
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\OneToOne(targetEntity: GH8054User::class, inversedBy: 'schedule')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public GH8054User|null $user = null;

    #[ORM\ManyToOne(targetEntity: GH8054User::class, inversedBy: 'schedules')]
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    public GH8054User|null $owner = null;
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH8054AssignedSchedule
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 42;

    public function __construct(
        #[ORM\OneToOne(targetEntity: GH8054User::class, inversedBy: 'assignedSchedule')]
        #[ORM\JoinColumn(nullable: false)]
        #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
        public GH8054User $user,
    ) {
    }
}
