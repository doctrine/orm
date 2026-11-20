<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('>=8.4.0')]
class NativeLazyObjectIdentityMapCollisionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConfiguration()->isNativeLazyObjectsEnabled()) {
            $this->markTestSkipped('Test requires native lazy objects enabled.');
        }

        $this->setUpEntitySchema([
            NativeLazyObjectIdentityMapCollisionEntity::class,
            NativeLazyObjectIdentityMapCollisionRelatedEntity::class,
            NativeLazyObjectIdentityMapCollisionEagerEntity::class,
            NativeLazyObjectIdentityMapCollisionLazyRelatedEntity::class,
            NativeLazyObjectIdentityMapCollisionLazyEntity::class,
        ]);
    }

    public function testInitializingDetachedReferenceWhenEntityIsAlreadyManaged(): void
    {
        $entity = new NativeLazyObjectIdentityMapCollisionEntity('value');
        $this->_em->persist($entity);
        $this->_em->flush();

        $id = $entity->id;
        self::assertNotNull($id);

        $this->_em->clear();
        $reference = $this->_em->getReference(NativeLazyObjectIdentityMapCollisionEntity::class, $id);
        $this->_em->clear();

        $managedEntity = $this->_em->find(NativeLazyObjectIdentityMapCollisionEntity::class, $id);
        self::assertNotNull($managedEntity);
        self::assertNotSame($managedEntity, $reference);

        self::assertSame('value', $reference->value);
        self::assertSame($managedEntity, $this->_em->find(NativeLazyObjectIdentityMapCollisionEntity::class, $id));
    }

    public function testInitializingDetachedReferenceWithEagerAssociationKeepsManagedEntity(): void
    {
        $related        = new NativeLazyObjectIdentityMapCollisionRelatedEntity();
        $entity         = new NativeLazyObjectIdentityMapCollisionEagerEntity('value', $related);
        $related->owner = $entity;
        $this->_em->persist($related);
        $this->_em->persist($entity);
        $this->_em->flush();

        $id = $entity->id;
        self::assertNotNull($id);

        $this->_em->clear();
        $reference = $this->_em->getReference(NativeLazyObjectIdentityMapCollisionEagerEntity::class, $id);
        $this->_em->clear();

        $managedEntity = $this->_em->find(NativeLazyObjectIdentityMapCollisionEagerEntity::class, $id);
        self::assertNotNull($managedEntity);
        self::assertNotSame($managedEntity, $reference);
        $managedRelated = $managedEntity->related;
        self::assertSame($managedEntity, $managedRelated->owner);
        $originalData        = $this->_em->getUnitOfWork()->getOriginalEntityData($managedEntity);
        $relatedOriginalData = $this->_em->getUnitOfWork()->getOriginalEntityData($managedRelated);

        $this->_em->createQuery(
            'UPDATE ' . NativeLazyObjectIdentityMapCollisionEagerEntity::class
            . ' e SET e.value = :value WHERE e.id = :id',
        )->setParameter('value', 'changed in database')
            ->setParameter('id', $id)
            ->execute();

        // Bulk DQL updates do not invalidate the second-level cache entry.
        $this->_em->getCache()?->evictEntity(NativeLazyObjectIdentityMapCollisionEagerEntity::class, $id);

        self::assertSame('changed in database', $reference->value);
        self::assertSame('value', $managedEntity->value);
        self::assertSame($managedEntity, $managedRelated->owner);
        self::assertFalse($this->_em->contains($reference));
        self::assertSame($originalData, $this->_em->getUnitOfWork()->getOriginalEntityData($managedEntity));
        self::assertSame($relatedOriginalData, $this->_em->getUnitOfWork()->getOriginalEntityData($managedRelated));
        self::assertSame($managedEntity, $this->_em->find(NativeLazyObjectIdentityMapCollisionEagerEntity::class, $id));

        $owner = $managedRelated->owner;
        self::assertNotNull($owner);
        $owner->value = 'saved through inverse';
        $this->_em->flush();
        $this->_em->clear();
        $reloaded = $this->_em->find(NativeLazyObjectIdentityMapCollisionEagerEntity::class, $id);
        self::assertNotNull($reloaded);
        self::assertSame('saved through inverse', $reloaded->value);
    }

    public function testInitializingDetachedReferenceKeepsLazyOneToOneBackReference(): void
    {
        $related        = new NativeLazyObjectIdentityMapCollisionLazyRelatedEntity();
        $entity         = new NativeLazyObjectIdentityMapCollisionLazyEntity('value', $related);
        $related->owner = $entity;
        $this->_em->persist($related);
        $this->_em->persist($entity);
        $this->_em->flush();

        $id = $entity->id;
        self::assertNotNull($id);

        $this->_em->clear();
        $reference = $this->_em->getReference(NativeLazyObjectIdentityMapCollisionLazyEntity::class, $id);
        $this->_em->clear();

        $managedEntity = $this->_em->find(NativeLazyObjectIdentityMapCollisionLazyEntity::class, $id);
        self::assertNotNull($managedEntity);
        $managedRelated = $managedEntity->related;
        self::assertSame($managedEntity, $managedRelated->owner);

        self::assertSame('value', $reference->value);
        self::assertSame($managedEntity, $managedRelated->owner);
        self::assertSame($managedEntity, $this->_em->find(NativeLazyObjectIdentityMapCollisionLazyEntity::class, $id));
    }
}

#[Entity]
class NativeLazyObjectIdentityMapCollisionEntity
{
    public function __construct(
        #[Column]
        public string $value,
    ) {
    }

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;
}

#[Entity]
class NativeLazyObjectIdentityMapCollisionEagerEntity
{
    public function __construct(
        #[Column]
        public string $value,
        #[OneToOne(
            targetEntity: NativeLazyObjectIdentityMapCollisionRelatedEntity::class,
            inversedBy: 'owner',
            fetch: 'EAGER',
        )]
        #[JoinColumn(nullable: false)]
        public NativeLazyObjectIdentityMapCollisionRelatedEntity $related,
    ) {
    }

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;
}

#[Entity]
class NativeLazyObjectIdentityMapCollisionRelatedEntity
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;

    #[OneToOne(targetEntity: NativeLazyObjectIdentityMapCollisionEagerEntity::class, mappedBy: 'related')]
    public NativeLazyObjectIdentityMapCollisionEagerEntity|null $owner = null;
}

#[Entity]
class NativeLazyObjectIdentityMapCollisionLazyEntity
{
    public function __construct(
        #[Column]
        public string $value,
        #[OneToOne(targetEntity: NativeLazyObjectIdentityMapCollisionLazyRelatedEntity::class, inversedBy: 'owner')]
        #[JoinColumn(nullable: false)]
        public NativeLazyObjectIdentityMapCollisionLazyRelatedEntity $related,
    ) {
    }

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;
}

#[Entity]
class NativeLazyObjectIdentityMapCollisionLazyRelatedEntity
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;

    #[OneToOne(targetEntity: NativeLazyObjectIdentityMapCollisionLazyEntity::class, mappedBy: 'related')]
    public NativeLazyObjectIdentityMapCollisionLazyEntity|null $owner = null;
}
