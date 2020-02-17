<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH7629Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH7629Entity::class,
        ]);

        $this->em->persist(new GH7629Entity());
        $this->em->flush();
        $this->em->clear();
    }

    public function testClearScheduledForSynchronizationWhenCommitEmpty() : void
    {
        $entity = $this->em->find(GH7629Entity::class, 1);

        $this->em->persist($entity);
        $this->em->flush();

        self::assertFalse($this->em->getUnitOfWork()->isScheduledForDirtyCheck($entity));
    }
}

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class GH7629Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}
