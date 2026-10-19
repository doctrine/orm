<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-11099')]
class GH11099Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->setUpEntitySchema([GH11099Project::class]);
        $cache = $this->_em->getCache();
        $cache->evictEntityRegion(GH11099Project::class);
        $cache->getQueryCache($cache->getEntityCacheRegion(GH11099Project::class)->getName())->clear();
        $cache->getQueryCache()->clear();
    }

    protected function tearDown(): void
    {
        $this->_em->getConnection()->executeStatement('DELETE FROM GH11099Project');

        parent::tearDown();
    }

    /** @return iterable<string, array{string, bool}> */
    public static function loadingPaths(): iterable
    {
        foreach (['find', 'reference', 'findBy', 'query'] as $method) {
            foreach ([false, true] as $withValues) {
                yield $method . ($withValues ? ' populated' : ' all-null') => [$method, $withValues];
            }
        }
    }

    #[DataProvider('loadingPaths')]
    public function testCachedEmbeddablesDoNotCausePhantomChanges(string $method, bool $withValues): void
    {
        $project = new GH11099Project();
        if ($withValues) {
            $project->invoiceAddress->city                  = 'Paris';
            $project->invoiceAddress->coordinates->latitude = '48.85';
            $project->shippingAddress->city                 = 'London';
        }

        $this->_em->persist($project);
        $this->_em->flush();
        $this->_em->clear();

        // Exercise both the cache entry published by flush and one repopulated
        // by SQL hydration after entity-region eviction.
        for ($i = 0; $i < 2; ++$i) {
            if ($method === 'findBy' || $method === 'query') {
                $this->loadProject($method);
                $this->_em->clear();
            }

            $this->getQueryLog()->reset()->enable();
            $loaded = $this->loadProject($method);
            $this->assertQueryCount(0);
            $invoice     = $loaded->invoiceAddress;
            $shipping    = $loaded->shippingAddress;
            $coordinates = $invoice->coordinates;
            self::assertSame($withValues ? 'Paris' : null, $invoice->city);
            self::assertSame($withValues ? '48.85' : null, $coordinates->latitude);
            self::assertSame($withValues ? 'London' : null, $shipping->city);
            self::assertNotSame($invoice, $shipping);

            $uow = $this->_em->getUnitOfWork();
            $uow->computeChangeSets();
            self::assertSame([], $uow->getEntityChangeSet($loaded));
            self::assertSame($invoice, $loaded->invoiceAddress);
            self::assertSame($shipping, $loaded->shippingAddress);
            self::assertSame($coordinates, $loaded->invoiceAddress->coordinates);

            $this->getQueryLog()->reset()->enable();
            $this->_em->flush();
            $this->assertQueryCount(0);
            self::assertSame($invoice, $loaded->invoiceAddress);

            if ($i === 0) {
                $this->_em->clear();
                $this->_em->getCache()->evictEntityRegion(GH11099Project::class);
                $this->loadProject('find');
                $this->_em->clear();
            }
        }

        $loaded->invoiceAddress->city                  = 'Berlin';
        $loaded->invoiceAddress->coordinates->latitude = '52.52';
        $uow->computeChangeSets();
        self::assertSame([
            'invoiceAddress.city' => [$withValues ? 'Paris' : null, 'Berlin'],
            'invoiceAddress.coordinates.latitude' => [$withValues ? '48.85' : null, '52.52'],
        ], $uow->getEntityChangeSet($loaded));
        $this->_em->flush();
        self::assertSame('Berlin', $this->_em->getConnection()->fetchOne('SELECT invoiceAddress_city FROM GH11099Project WHERE id = 1'));
        $this->_em->clear();

        $reloaded = $this->loadProject($method);
        self::assertSame('Berlin', $reloaded->invoiceAddress->city);
        self::assertSame('52.52', $reloaded->invoiceAddress->coordinates->latitude);
        $uow->computeChangeSets();
        self::assertSame([], $uow->getEntityChangeSet($reloaded));
        $this->getQueryLog()->reset()->enable();
        $this->_em->flush();
        $this->assertQueryCount(0);
    }

    private function loadProject(string $method): GH11099Project
    {
        return match ($method) {
            'find' => $this->_em->find(GH11099Project::class, 1),
            'reference' => $this->initializeReference(),
            'findBy' => $this->_em->getRepository(GH11099Project::class)->findBy(['id' => 1])[0],
            'query' => $this->_em->createQuery('SELECT p FROM ' . GH11099Project::class . ' p')->setCacheable(true)->getSingleResult(),
        };
    }

    private function initializeReference(): GH11099Project
    {
        $project = $this->_em->getReference(GH11099Project::class, 1);
        $this->_em->initializeObject($project);

        return $project;
    }
}

#[ORM\Entity]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class GH11099Project
{
    #[ORM\Id]
    #[ORM\Column]
    public int $id = 1;

    #[ORM\Embedded(class: GH11099Address::class)]
    public GH11099Address $invoiceAddress;

    #[ORM\Embedded(class: GH11099Address::class)]
    public GH11099Address $shippingAddress;

    public function __construct()
    {
        $this->invoiceAddress  = new GH11099Address();
        $this->shippingAddress = new GH11099Address();
    }
}

#[ORM\Embeddable]
class GH11099Address
{
    #[ORM\Column(length: 100, nullable: true)]
    public string|null $city = null;

    #[ORM\Embedded(class: GH11099Coordinates::class)]
    public GH11099Coordinates $coordinates;

    public function __construct()
    {
        $this->coordinates = new GH11099Coordinates();
    }
}

#[ORM\Embeddable]
class GH11099Coordinates
{
    #[ORM\Column(length: 20, nullable: true)]
    public string|null $latitude = null;
}
