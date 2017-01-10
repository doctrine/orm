<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Test that Doctrine ORM correctly works with the ObjectManagerAware and PersistentObject
 * classes from Common.
 *
 * @group DDC-1448
 */
class PersistentObjectTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(PersistentEntity::class),
                ]
            );
        } catch (\Exception $e) {
        }

        PersistentObject::setObjectManager($this->em);
    }

    public function testPersist()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");

        $this->em->persist($entity);
        $this->em->flush();

        $this->addToAssertionCount(1);
    }

    public function testFind()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->find(PersistentEntity::class, $entity->getId());

        self::assertEquals('test', $entity->getName());
        $entity->setName('foobar');

        $this->em->flush();
    }

    public function testGetReference()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->getReference(PersistentEntity::class, $entity->getId());

        self::assertEquals('test', $entity->getName());
    }

    public function testSetAssociation()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");
        $entity->setParent($entity);

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->getReference(PersistentEntity::class, $entity->getId());
        self::assertSame($entity, $entity->getParent());
    }
}

/**
 * @Entity
 */
class PersistentEntity extends PersistentObject
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ManyToOne(targetEntity="PersistentEntity")
     * @var PersistentEntity
     */
    protected $parent;
}
