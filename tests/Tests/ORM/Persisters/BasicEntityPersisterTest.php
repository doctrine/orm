<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Persisters\Entity\BasicEntityPersister;
use Doctrine\Tests\Models\Forum\ForumUser;
use Doctrine\Tests\Models\MixedToOneIdentity\Country;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

use function method_exists;

class BasicEntityPersisterTest extends OrmTestCase
{
    public function testExecuteInsertsWithNoQueuedInserts(): void
    {
        $connection = $this->spyConnection();

        $connection->expects(self::never())
            ->method('prepare');
        $entityManager = $this->createTestEntityManagerWithConnection($connection);

        $persister = new BasicEntityPersister($entityManager, $entityManager->getClassMetadata(Country::class));

        $persister->executeInserts();
    }

    public function testWillExecuteInsertsInBatches(): void
    {
        $connection = $this->spyConnection();

        $statement1 = $this->createMock(Statement::class);
        $statement2 = $this->createMock(Statement::class);

        // Statement is to be prepared twice:
        //  * once with the wished batch size
        //  * once with the leftover items
        $connection->expects(self::exactly(2))
            ->method('prepare')
            ->with(self::logicalOr(
                'INSERT INTO Country (country) VALUES (?),(?)',
                'INSERT INTO Country (country) VALUES (?)',
            ))
            ->willReturnOnConsecutiveCalls($statement1, $statement2);

        $statement1->expects(self::exactly(2))
            ->method('executeStatement');
        $statement2->expects(self::once())
            ->method('executeStatement');

        $entityManager = $this->createTestEntityManagerWithConnection($connection);

        $entityManager->getConfiguration()
            ->setPersisterMaximumInsertBatchSize(2);

        $persister = new BasicEntityPersister($entityManager, $entityManager->getClassMetadata(Country::class));

        $country1 = new Country();
        $country2 = new Country();
        $country3 = new Country();
        $country4 = new Country();
        $country5 = new Country();

        $country1->country = 'Italy';
        $country2->country = 'Germany';
        $country3->country = 'Austria';
        $country4->country = 'Spain';
        $country5->country = 'France';

        $unitOfWork = $entityManager->getUnitOfWork();

        $unitOfWork->persist($country1);
        $unitOfWork->persist($country2);
        $unitOfWork->persist($country3);
        $unitOfWork->persist($country4);
        $unitOfWork->persist($country5);

        $unitOfWork->computeChangeSets();

        $persister->addInsert($country1);
        $persister->addInsert($country2);
        $persister->addInsert($country3);
        $persister->addInsert($country4);
        $persister->addInsert($country5);

        $persister->executeInserts();
    }

    public function testWillExecuteASingleBatchIfBatchSizeIsBiggerThanPersistedEntitiesCount(): void
    {
        $connection = $this->spyConnection();

        $statement = $this->createMock(Statement::class);

        // Statement is to be prepared twice:
        //  * once with the wished batch size
        //  * once with the leftover items
        $connection->expects(self::exactly(1))
            ->method('prepare')
            ->with('INSERT INTO Country (country) VALUES (?),(?),(?),(?),(?)')
            ->willReturn($statement);

        $statement->expects(self::once())
            ->method('executeStatement');

        $entityManager = $this->createTestEntityManagerWithConnection($connection);

        $entityManager->getConfiguration()
            ->setPersisterMaximumInsertBatchSize(100);

        $persister = new BasicEntityPersister($entityManager, $entityManager->getClassMetadata(Country::class));

        $country1 = new Country();
        $country2 = new Country();
        $country3 = new Country();
        $country4 = new Country();
        $country5 = new Country();

        $country1->country = 'Italy';
        $country2->country = 'Germany';
        $country3->country = 'Austria';
        $country4->country = 'Spain';
        $country5->country = 'France';

        $unitOfWork = $entityManager->getUnitOfWork();

        $unitOfWork->persist($country1);
        $unitOfWork->persist($country2);
        $unitOfWork->persist($country3);
        $unitOfWork->persist($country4);
        $unitOfWork->persist($country5);

        $unitOfWork->computeChangeSets();

        $persister->addInsert($country1);
        $persister->addInsert($country2);
        $persister->addInsert($country3);
        $persister->addInsert($country4);
        $persister->addInsert($country5);

        $persister->executeInserts();
    }

    public function testWillNotBatchStatementsForEntitiesWithPostInsertIdGeneratedValues(): void
    {
        $connection = $this->spyConnection();

        $statement = $this->createMock(Statement::class);

        // Statement is to be prepared twice:
        //  * once with the wished batch size
        //  * once with the leftover items
        $connection->expects(self::exactly(1))
            ->method('prepare')
            ->with(self::logicalOr(
                'INSERT INTO forum_users (username,avatar_id) VALUES (?,?)',
            ))
            ->willReturn($statement);

        $statement->expects(self::exactly(3))
            ->method('executeStatement');

        $entityManager = $this->createTestEntityManagerWithConnection($connection);

        $entityManager->getConfiguration()
            ->setPersisterMaximumInsertBatchSize(100);

        $persister = new BasicEntityPersister($entityManager, $entityManager->getClassMetadata(ForumUser::class));

        $entity1     = new ForumUser();
        $entity2     = new ForumUser();
        $entity3     = new ForumUser();
        $entity1->id = 1;
        $entity2->id = 2;
        $entity3->id = 3;

        $unitOfWork = $entityManager->getUnitOfWork();

        $unitOfWork->registerManaged($entity1, ['id' => 1], ['id' => 1]);
        $unitOfWork->registerManaged($entity2, ['id' => 2], ['id' => 2]);
        $unitOfWork->registerManaged($entity3, ['id' => 3], ['id' => 3]);

        $unitOfWork->computeChangeSets();

        $persister->addInsert($entity1);
        $persister->addInsert($entity2);
        $persister->addInsert($entity3);

        $persister->executeInserts();
    }

    private function spyConnection(): Connection&MockObject
    {
        $driver = $this->createStub(Driver::class);
        $driver->method('connect')
            ->willReturn($this->createStub(Driver\Connection::class));

        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('supportsIdentityColumns')
            ->willReturn(true);

        $connection = $this->createMock(Connection::class);

        if (method_exists($connection, 'getEventManager')) {
            $connection->method('getEventManager')
                ->willReturn(new EventManager());
        }

        $connection->method('lastInsertId')
            ->willReturnOnConsecutiveCalls(1, 2, 3);
        $connection->method('convertToPHPValue')
            ->willReturnArgument(0);

        return $connection;
    }
}
