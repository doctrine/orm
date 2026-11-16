<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
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
            NativeLazyObjectIdentityMapCollisionEagerEntity::class,
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
        $related = new NativeLazyObjectIdentityMapCollisionEntity('related');
        $entity  = new NativeLazyObjectIdentityMapCollisionEagerEntity('value', $related);
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
        $originalData = $this->_em->getUnitOfWork()->getOriginalEntityData($managedEntity);

        $this->_em->createQuery('UPDATE ' . NativeLazyObjectIdentityMapCollisionEagerEntity::class . ' e SET e.value = :value WHERE e.id = :id')
            ->setParameter('value', 'changed in database')
            ->setParameter('id', $id)
            ->execute();

        self::assertSame('changed in database', $reference->value);
        self::assertSame('value', $managedEntity->value);
        self::assertFalse($this->_em->contains($reference));
        self::assertSame($originalData, $this->_em->getUnitOfWork()->getOriginalEntityData($managedEntity));
        self::assertSame($managedEntity, $this->_em->find(NativeLazyObjectIdentityMapCollisionEagerEntity::class, $id));
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
    public ?int $id = null;
}

#[Entity]
class NativeLazyObjectIdentityMapCollisionEagerEntity
{
    public function __construct(
        #[Column]
        public string $value,
        #[ManyToOne(targetEntity: NativeLazyObjectIdentityMapCollisionEntity::class, fetch: 'EAGER')]
        public NativeLazyObjectIdentityMapCollisionEntity $related,
    ) {
    }

    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public ?int $id = null;
}
