<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\AttachEntityListenersListener;
use Doctrine\Tests\OrmTestCase;

use function func_get_args;

class AttachEntityListenersListenerTest extends OrmTestCase
{
    private EntityManagerInterface $em;

    private AttachEntityListenersListener $listener;

    private ClassMetadataFactory $factory;

    protected function setUp(): void
    {
        $this->listener = new AttachEntityListenersListener();
        $driver         = $this->createAttributeDriver();
        $this->em       = $this->getTestEntityManager();
        $evm            = $this->em->getEventManager();
        $this->factory  = new ClassMetadataFactory();

        $evm->addEventListener(Events::loadClassMetadata, $this->listener);
        $this->em->getConfiguration()->setMetadataDriverImpl($driver);
        $this->factory->setEntityManager($this->em);
    }

    public function testAttachEntityListeners(): void
    {
        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestFooEntity::class,
            AttachEntityListenersListenerTestListener::class,
            Events::postLoad,
            'postLoadHandler',
        );

        $metadata = $this->factory->getMetadataFor(AttachEntityListenersListenerTestFooEntity::class);

        self::assertArrayHasKey('postLoad', $metadata->entityListeners);
        self::assertCount(1, $metadata->entityListeners['postLoad']);
        self::assertEquals('postLoadHandler', $metadata->entityListeners['postLoad'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['postLoad'][0]['class']);

        // Can reattach entity listeners even class metadata factory recreated.
        $factory2 = new ClassMetadataFactory();
        $factory2->setEntityManager($this->em);

        $metadata2 = $factory2->getMetadataFor(AttachEntityListenersListenerTestFooEntity::class);

        self::assertArrayHasKey('postLoad', $metadata2->entityListeners);
        self::assertCount(1, $metadata2->entityListeners['postLoad']);
        self::assertEquals('postLoadHandler', $metadata2->entityListeners['postLoad'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata2->entityListeners['postLoad'][0]['class']);
    }

    public function testAttachToExistingEntityListeners(): void
    {
        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestBarEntity::class,
            AttachEntityListenersListenerTestListener2::class,
            Events::prePersist,
        );

        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestBarEntity::class,
            AttachEntityListenersListenerTestListener2::class,
            Events::postPersist,
            'postPersistHandler',
        );

        $metadata = $this->factory->getMetadataFor(AttachEntityListenersListenerTestBarEntity::class);

        self::assertArrayHasKey('postPersist', $metadata->entityListeners);
        self::assertArrayHasKey('prePersist', $metadata->entityListeners);

        self::assertCount(2, $metadata->entityListeners['prePersist']);
        self::assertCount(2, $metadata->entityListeners['postPersist']);

        self::assertEquals('prePersist', $metadata->entityListeners['prePersist'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['prePersist'][0]['class']);

        self::assertEquals('prePersist', $metadata->entityListeners['prePersist'][1]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener2::class, $metadata->entityListeners['prePersist'][1]['class']);

        self::assertEquals('postPersist', $metadata->entityListeners['postPersist'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['postPersist'][0]['class']);

        self::assertEquals('postPersistHandler', $metadata->entityListeners['postPersist'][1]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener2::class, $metadata->entityListeners['postPersist'][1]['class']);
    }

    public function testDuplicateEntityListenerException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Entity Listener "Doctrine\Tests\ORM\Tools\AttachEntityListenersListenerTestListener#postPersist()" in "Doctrine\Tests\ORM\Tools\AttachEntityListenersListenerTestFooEntity" was already declared, but it must be declared only once.');
        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestFooEntity::class,
            AttachEntityListenersListenerTestListener::class,
            Events::postPersist,
        );

        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestFooEntity::class,
            AttachEntityListenersListenerTestListener::class,
            Events::postPersist,
        );

        $this->factory->getMetadataFor(AttachEntityListenersListenerTestFooEntity::class);
    }

    public function testAttachWithoutSpecifyingAnEventName(): void
    {
        $this->listener->addEntityListener(
            AttachEntityListenersListenerTestFooEntity::class,
            AttachEntityListenersListenerTestListener::class,
        );

        $metadata = $this->factory->getMetadataFor(AttachEntityListenersListenerTestFooEntity::class);

        self::assertCount(2, $metadata->entityListeners);

        self::assertArrayHasKey('prePersist', $metadata->entityListeners);
        self::assertArrayHasKey('postPersist', $metadata->entityListeners);

        self::assertCount(1, $metadata->entityListeners['prePersist']);
        self::assertCount(1, $metadata->entityListeners['postPersist']);

        self::assertEquals('prePersist', $metadata->entityListeners['prePersist'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['prePersist'][0]['class']);

        self::assertEquals('postPersist', $metadata->entityListeners['postPersist'][0]['method']);
        self::assertEquals(AttachEntityListenersListenerTestListener::class, $metadata->entityListeners['postPersist'][0]['class']);
    }
}

#[Entity]
class AttachEntityListenersListenerTestFooEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;
}

#[Entity]
#[EntityListeners(['AttachEntityListenersListenerTestListener'])]
class AttachEntityListenersListenerTestBarEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;
}

class AttachEntityListenersListenerTestListener
{
    /** @phpstan-var array<string,list<list<mixed>>> */
    public $calls;

    public function prePersist(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postLoadHandler(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postPersist(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }
}

class AttachEntityListenersListenerTestListener2
{
    /** @phpstan-var array<string,list<list<mixed>>> */
    public $calls;

    public function prePersist(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }

    public function postPersistHandler(): void
    {
        $this->calls[__FUNCTION__][] = func_get_args();
    }
}
