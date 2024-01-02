<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\Tests\Models\PersistentObject\PersistentEntity;
use Doctrine\Tests\OrmFunctionalTestCase;

use function class_exists;

/**
 * Test that Doctrine ORM correctly works with the ObjectManagerAware and PersistentObject
 * classes from Common.
 *
 * @group DDC-1448
 */
class PersistentObjectTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(PersistentObject::class)) {
            self::markTestSkipped('This test requires doctrine/persistence 2');
        }

        parent::setUp();

        $this->createSchemaForModels(PersistentEntity::class);

        PersistentObject::setObjectManager($this->_em);
    }

    public function testPersist(): void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->addToAssertionCount(1);
    }

    public function testFind(): void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(PersistentEntity::class, $entity->getId());

        self::assertEquals('test', $entity->getName());
        $entity->setName('foobar');

        $this->_em->flush();
    }

    public function testGetReference(): void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->getReference(PersistentEntity::class, $entity->getId());

        self::assertEquals('test', $entity->getName());
    }

    public function testSetAssociation(): void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');
        $entity->setParent($entity);

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->getReference(PersistentEntity::class, $entity->getId());
        self::assertSame($entity, $entity->getParent());
    }
}
