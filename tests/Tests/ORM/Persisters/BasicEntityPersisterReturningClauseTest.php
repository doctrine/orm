<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\Identity\Cat;
use Doctrine\Tests\Models\Identity\Dog;
use Doctrine\Tests\OrmTestCase;
use ReflectionMethod;

class BasicEntityPersisterReturningClauseTest extends OrmTestCase
{
    private EntityManagerMock $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = $this->createMock(EventManager::class);
        $evm->method('hasListeners')
            ->willReturn(false);

        $platform = $this->createMock(PostgreSQLPlatform::class);
        $platform->method('getEmptyIdentityInsertSQL')
            ->willReturnCallback(static function (string $table, string $column): string {
                return 'INSERT INTO ' . $table . ' (' . $column . ') VALUES (DEFAULT)';
            });

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')
            ->willReturn($platform);
        $connection->method('getEventManager')
            ->willReturn($evm);

        $this->entityManager = $this->createTestEntityManagerWithConnection($connection);
        $this->entityManager->getConfiguration()->setUseReturningClauseForGeneratingId(true);
    }

    public function testEmptyIdentityInsertSQL(): void
    {
        $persister = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(Dog::class));
        $method    = new ReflectionMethod($persister, 'getInsertSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($persister);

        self::assertSame('INSERT INTO Dog (id) VALUES (DEFAULT) RETURNING id', $sql);
    }

    public function testInsertSqlWithColumn(): void
    {
        $persister = new BasicEntityPersister($this->entityManager, $this->entityManager->getClassMetadata(Cat::class));
        $method    = new ReflectionMethod($persister, 'getInsertSQL');
        $method->setAccessible(true);

        $sql = $method->invoke($persister);

        self::assertSame('INSERT INTO Cat (name) VALUES (?) RETURNING id', $sql);
    }
}
