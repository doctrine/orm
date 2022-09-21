<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister;
use Doctrine\Tests\Mocks\EntityManagerMock;
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

    /** @var EntityManagerMock */
    protected $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em        = $this->getTestEntityManager();
        $this->persister = new JoinedSubclassPersister($this->em, $this->em->getClassMetadata(RootClass::class));
    }

    /** @group DDC-3470 */
    public function testExecuteInsertsWillReturnEmptySetWithNoQueuedInserts(): void
    {
        self::assertSame([], $this->persister->executeInserts());
    }
}
