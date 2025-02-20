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

/**
 * PrePersistEventTest
 */
class PrePersistEventTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            EntityWithUnmapEntity::class,
            EntityWithCascadeAssociation::class
        );
    }

    public function testCallingPersistInPrePersistHook(): void
    {
        $entityWithUnmapped = new EntityWithUnmapEntity();
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

        if ($object instanceof EntityWithUnmapEntity) {
            $uow = $args->getObjectManager()->getUnitOfWork();

            if ($object->unmapped && ! $uow->isInIdentityMap($object->unmapped) && ! $uow->isScheduledForInsert($object->unmapped)) {
                $args->getObjectManager()->persist($object->unmapped);
            }
        }
    }
}

/** @Entity */
#[Entity]
class EntityWithUnmapEntity
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=255)
     * @GeneratedValue(strategy="NONE")
     */
    #[Id]
    #[Column(type: 'string', length: 255)]
    #[GeneratedValue(strategy: 'NONE')]
    public $id;

    /** @var ?EntityWithCascadeAssociation  */
    public $unmapped = null;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}

/** @Entity */
#[Entity]
class EntityWithCascadeAssociation
{
    /**
     * @var string
     * @Id
     * @Column(type="string", length=255)
     * @GeneratedValue(strategy="NONE")
     */
    #[Id]
    #[Column(type: 'string', length: 255)]
    #[GeneratedValue(strategy: 'NONE')]
    public $id;

    /**
     * @var ?EntityWithUnmapEntity
     * @ManyToOne(targetEntity=EntityWithUnmapEntity::class, cascade={"persist"})
     */
    #[ManyToOne(targetEntity: EntityWithUnmapEntity::class, cascade: ['persist'])]
    public $cascaded = null;

    public function __construct()
    {
        $this->id = uniqid(self::class, true);
    }
}
