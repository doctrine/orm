<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\OrmFunctionalTestCase;

use function uniqid;

class PrePersistEventTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            EntityWithUnmappedEntity::class,
            EntityWithCascadeAssociation::class,
        );
    }

    public function testCallingPersistInPrePersistHook(): void
    {
        $entityWithUnmapped = new EntityWithUnmappedEntity();
        $entityWithCascade  = new EntityWithCascadeAssociation();

        $entityWithUnmapped->unmapped = $entityWithCascade;
        $entityWithCascade->cascaded  = $entityWithUnmapped;

        $this->_em->getEventManager()->addEventListener(Events::prePersist, new PrePersistUnmappedPersistListener());
        $this->_em->persist($entityWithUnmapped);

        $this->assertTrue($this->_em->getUnitOfWork()->isScheduledForInsert($entityWithCascade));
        $this->assertTrue($this->_em->getUnitOfWork()->isScheduledForInsert($entityWithUnmapped));
    }
}

class PrePersistUnmappedPersistListener
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $object = $args->getObject();

        if ($object instanceof EntityWithUnmappedEntity) {
            $uow = $args->getObjectManager()->getUnitOfWork();

            if ($object->unmapped && ! $uow->isInIdentityMap($object->unmapped) && ! $uow->isScheduledForInsert($object->unmapped)) {
                $args->getObjectManager()->persist($object->unmapped);
            }
        }
    }
}

#[Entity]
class EntityWithUnmappedEntity
{
    #[Id]
    #[Column(type: 'string', length: 255)]
    #[GeneratedValue(strategy: 'NONE')]
    public string $id;

    public EntityWithCascadeAssociation|null $unmapped = null;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

#[Entity]
class EntityWithCascadeAssociation
{
    #[Id]
    #[Column(type: 'string', length: 255)]
    #[GeneratedValue(strategy: 'NONE')]
    public string $id;

    #[ManyToOne(targetEntity: EntityWithUnmappedEntity::class, cascade: ['persist'])]
    public EntityWithUnmappedEntity|null $cascaded = null;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}
