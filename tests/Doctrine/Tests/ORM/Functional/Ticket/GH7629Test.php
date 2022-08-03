<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class GH7629Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH7629Entity::class,
        ]);

        $this->_em->persist(new GH7629Entity());
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testClearScheduledForSynchronizationWhenCommitEmpty(): void
    {
        $entity = $this->_em->find(GH7629Entity::class, 1);

        $this->_em->persist($entity);
        $this->_em->flush();

        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForDirtyCheck($entity));
    }

    /**
     * @group GH-8231
     */
    public function testPersistAfterRemoveSchedulesForSynchronization(): void
    {
        $entity = $this->_em->find(GH7629Entity::class, 1);

        $this->_em->remove($entity);

        $this->_em->persist($entity);

        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForDirtyCheck($entity));
    }
}

/**
 * @Entity
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class GH7629Entity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}
