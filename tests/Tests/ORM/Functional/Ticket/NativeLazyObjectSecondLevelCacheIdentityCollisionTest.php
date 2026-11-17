<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('>=8.4.0')]
class NativeLazyObjectSecondLevelCacheIdentityCollisionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        if (! $this->_em->getConfiguration()->isNativeLazyObjectsEnabled()) {
            $this->markTestSkipped('Test requires native lazy objects enabled.');
        }

        $this->setUpEntitySchema([
            NativeLazyObjectCachedServer::class,
            NativeLazyObjectCachedAccountType::class,
        ]);
    }

    public function testInitializingDetachedReferenceFromCacheKeepsManagedEagerAssociation(): void
    {
        $server      = new NativeLazyObjectCachedServer('Server');
        $accountType = new NativeLazyObjectCachedAccountType($server);
        $this->_em->persist($server);
        $this->_em->persist($accountType);
        $this->_em->flush();

        $serverId      = $server->id;
        $accountTypeId = $accountType->id;
        self::assertNotNull($serverId);
        self::assertNotNull($accountTypeId);

        $cache = $this->_em->getCache();
        self::assertNotNull($cache);
        self::assertTrue($cache->containsEntity(NativeLazyObjectCachedServer::class, $serverId));
        self::assertTrue($cache->containsEntity(NativeLazyObjectCachedAccountType::class, $accountTypeId));

        $this->_em->clear();
        $detachedServer = $this->_em->getReference(NativeLazyObjectCachedServer::class, $serverId);
        $this->_em->clear();

        $loadedAccountType = $this->_em->find(NativeLazyObjectCachedAccountType::class, $accountTypeId);
        self::assertNotNull($loadedAccountType);
        $managedServer = $loadedAccountType->server;
        self::assertNotSame($managedServer, $detachedServer);

        self::assertSame('Server', $detachedServer->name);
        self::assertSame($managedServer, $loadedAccountType->server);
        self::assertSame($managedServer, $this->_em->find(NativeLazyObjectCachedServer::class, $serverId));
        self::assertFalse($this->_em->contains($detachedServer));
    }
}

#[Entity]
#[Table(name: 'native_lazy_cached_server')]
#[Cache(usage: 'NONSTRICT_READ_WRITE', region: 'native_lazy_cached_server')]
class NativeLazyObjectCachedServer
{
    public function __construct(
        #[Column]
        public string $name,
    ) {
    }

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;
}

#[Entity]
#[Table(name: 'native_lazy_cached_account_type')]
#[Cache(usage: 'NONSTRICT_READ_WRITE', region: 'native_lazy_cached_account_type')]
class NativeLazyObjectCachedAccountType
{
    public function __construct(
        #[Cache(usage: 'NONSTRICT_READ_WRITE')]
        #[ManyToOne(targetEntity: NativeLazyObjectCachedServer::class, fetch: 'EAGER')]
        #[JoinColumn(nullable: false)]
        public NativeLazyObjectCachedServer $server,
    ) {
    }

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;
}
