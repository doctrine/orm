<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister;
use Doctrine\Tests\Models\JoinedInheritanceType\RootClass;
use Doctrine\Tests\OrmTestCase;

/**
 * Tests for {@see \Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister}
 *
 * @covers \Doctrine\ORM\Persisters\Entity\JoinedSubclassPersister
 */
class JoinedSubClassPersisterTest extends OrmTestCase
{
    /** @var JoinedSubclassPersister */
    protected $persister;

    /** @var EntityManager */
    protected $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em        = $this->_getTestEntityManager();
        $this->persister = new JoinedSubclassPersister($this->em, $this->em->getClassMetadata(RootClass::class));
    }

    /**
     * @group DDC-3470
     */
    public function testExecuteInsertsWillReturnEmptySetWithNoQueuedInserts(): void
    {
        $this->assertSame([], $this->persister->executeInserts());
    }
}
