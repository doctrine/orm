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
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(PersistentEntity::class),
                ]
            );
        } catch (\Exception $e) {
        }

        PersistentObject::setObjectManager($this->_em);
    }

    public function testPersist()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->addToAssertionCount(1);
    }

    public function testFind()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(PersistentEntity::class, $entity->getId());

        $this->assertEquals('test', $entity->getName());
        $entity->setName('foobar');

        $this->_em->flush();
    }

    public function testGetReference()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->getReference(PersistentEntity::class, $entity->getId());

        $this->assertEquals('test', $entity->getName());
    }

    public function testSetAssociation()
    {
        $entity = new PersistentEntity();
        $entity->setName("test");
        $entity->setParent($entity);

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->getReference(PersistentEntity::class, $entity->getId());
        $this->assertSame($entity, $entity->getParent());
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
