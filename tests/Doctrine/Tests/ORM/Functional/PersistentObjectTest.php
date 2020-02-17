<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\PersistentObject;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

/**
 * Test that Doctrine ORM correctly works with the EntityManagerAware and PersistentObject
 * classes from Common.
 *
 * @group DDC-1448
 */
class PersistentObjectTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(PersistentEntity::class),
                ]
            );
        } catch (Exception $e) {
        }

        PersistentObject::setEntityManager($this->em);
    }

    public function testPersist() : void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');

        $this->em->persist($entity);
        $this->em->flush();

        $this->addToAssertionCount(1);
    }

    public function testFind() : void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->find(PersistentEntity::class, $entity->getId());

        self::assertEquals('test', $entity->getName());
        $entity->setName('foobar');

        $this->em->flush();
    }

    public function testGetReference() : void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->getReference(PersistentEntity::class, $entity->getId());

        self::assertEquals('test', $entity->getName());
    }

    public function testSetAssociation() : void
    {
        $entity = new PersistentEntity();
        $entity->setName('test');
        $entity->setParent($entity);

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();

        $entity = $this->em->getReference(PersistentEntity::class, $entity->getId());
        self::assertSame($entity, $entity->getParent());
    }
}

/**
 * @ORM\Entity
 */
class PersistentEntity extends PersistentObject
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity=PersistentEntity::class)
     *
     * @var PersistentEntity
     */
    protected $parent;
}
