<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\ComparatorRegistry;
use Doctrine\Tests\Models\TypedProperties\UserTyped;
use Doctrine\Tests\OrmFunctionalTestCase;

class ComparatorRegistryBasedChangeDetectionTest extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        ComparatorRegistry::reset();
        parent::setUp();

        $this->setUpEntitySchema([UserTyped::class]);
    }

    protected function tearDown(): void
    {
        ComparatorRegistry::reset();
    }

    public function testChangingDateTimeInstanceWithoutComparator(): void
    {
        $user = new UserTyped();
        $user->dateTime = new \DateTime();
        $this->initializeChangesetState($user);

        $user->dateTime = clone $user->dateTime;
        $this->recomputeChangeset($user);

        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForUpdate($user));
    }

    public function testChangingDateTimeInstanceWithComparator(): void
    {
        ComparatorRegistry::register(\DateTimeInterface::class, function (\DateTimeInterface $a, object $b) {
            if ($b instanceof \DateTimeInterface) {
                return $a <=> $b;
            }
        });

        $user = new UserTyped();
        $user->dateTime = new \DateTime();
        $this->initializeChangesetState($user);

        $user->dateTime = clone $user->dateTime;
        $this->recomputeChangeset($user);

        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForUpdate($user));
    }

    public function testChangingMutableObject(): void
    {
        ComparatorRegistry::register(\DateTimeInterface::class, function (\DateTimeInterface $a, object $b) {
            if ($b instanceof \DateTimeInterface) {
                return $a <=> $b;
            }
        });

        $user = new UserTyped();
        $user->dateTime = new \DateTime();
        $this->initializeChangesetState($user);

        $user->dateTime->add(new \DateInterval('P7D'));
        $this->recomputeChangeset($user);

        self::assertTrue($this->_em->getUnitOfWork()->isScheduledForUpdate($user));
    }

    private function initializeChangesetState(object $entity): void
    {
        // Initialize UoW state
        $this->_em->persist($entity);
        $cm = $this->_em->getClassMetadata(get_class($entity));
        $this->_em->getUnitOfWork()->computeChangeSet($cm, $entity);

        // Run change set computation with no changes
        $this->_em->getUnitOfWork()->computeChangeSet($cm, $entity);

        // sanity check
        self::assertFalse($this->_em->getUnitOfWork()->isScheduledForUpdate($entity));
    }

    private function recomputeChangeset(object $entity): void
    {
        $cm = $this->_em->getClassMetadata(get_class($entity));
        $this->_em->getUnitOfWork()->computeChangeSet($cm, $entity);
    }
}
