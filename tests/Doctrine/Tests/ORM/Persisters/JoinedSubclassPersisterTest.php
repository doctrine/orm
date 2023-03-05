<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister;
use Doctrine\Tests\Mocks\EntityManagerMock;
use Doctrine\Tests\Models\JoinedInheritanceType\RootClass;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for {@see \Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister}
 */
#[CoversClass(JoinedSubclassPersister::class)]
class JoinedSubclassPersisterTest extends OrmTestCase
{
    protected JoinedSubclassPersister $persister;
    protected EntityManagerMock $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em        = $this->getTestEntityManager();
        $this->persister = new JoinedSubclassPersister($this->em, $this->em->getClassMetadata(RootClass::class));
    }

    #[Group('DDC-3470')]
    public function testExecuteInsertsWillReturnEmptySetWithNoQueuedInserts(): void
    {
        self::assertSame([], $this->persister->executeInserts());
    }
}
