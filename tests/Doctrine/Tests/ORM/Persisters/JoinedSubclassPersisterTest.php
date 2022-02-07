<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister;
use Doctrine\Tests\Mocks\ConnectionMock;
use Doctrine\Tests\Models\JoinedInheritanceType\AnotherChildClass;
use Doctrine\Tests\Models\JoinedInheritanceType\ChildClassWithNonWritableFields;
use Doctrine\Tests\Models\JoinedInheritanceType\RootClass;
use Doctrine\Tests\OrmTestCase;

/**
 * Tests for {@see \Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister}
 *
 * @covers \Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister
 */
class JoinedSubclassPersisterTest extends OrmTestCase
{
    /** @var JoinedSubclassPersister */
    protected $persister;

    /** @var EntityManagerInterface */
    protected $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em        = $this->getTestEntityManager();
        $this->persister = new JoinedSubclassPersister($this->em, $this->em->getClassMetadata(RootClass::class));
    }

    /**
     * @group DDC-3470
     */
    public function testExecuteInsertsWillReturnEmptySetWithNoQueuedInserts(): void
    {
        self::assertSame([], $this->persister->executeInserts());
    }

    public function testNonInsertablePropertyInChildClass(): void
    {
        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($this->createAnnotationDriver([__DIR__ . '/../../Models/JoinedInheritanceType/']));
        $conn = $em->getConnection();
        assert($conn instanceof ConnectionMock);
        $logger = new DebugStack();
        $conn->getConfiguration()->setSQLLogger($logger);
        $entity = new ChildClassWithNonWritableFields();
        $em->persist($entity);
        $em->flush();

        $subClassInsertQuery = $logger->queries[\count($logger->queries) - 1];
        self::assertSame($subClassInsertQuery['sql'], 'INSERT INTO ChildClassWithNonWritableFields (id, nonUpdatableContent, writableContent) VALUES (?, ?, ?)', 'Non-insertable fields must be absent from query.');
        self::assertCount(3, $subClassInsertQuery['params'], 'Non-insertable fields must be absent from params.');
    }
}
