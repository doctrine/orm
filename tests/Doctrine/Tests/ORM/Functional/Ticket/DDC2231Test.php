<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as CommonMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectManagerAware;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerAware;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\ToolsException;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @group DDC-2231
 */
class DDC2231Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var DDC2231EntityManagerAwareEntity
     */
    private $persistedEntityManagerAwareEntity;

    /**
     * @var DDC2231ObjectManagerAwareEntity
     */
    private $persistedObjectManagerAwareEntity;

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema([
                $this->em->getClassMetadata(DDC2231EntityManagerAwareEntity::class),
                $this->em->getClassMetadata(DDC2231ObjectManagerAwareEntity::class),
            ]);
        } catch (ToolsException $ignored) {
            // ignore - schema already exists
        }

        $this->persistedEntityManagerAwareEntity = new DDC2231EntityManagerAwareEntity();
        $this->persistedObjectManagerAwareEntity = new DDC2231ObjectManagerAwareEntity();

        $this->em->persist($this->persistedEntityManagerAwareEntity);
        $this->em->persist($this->persistedObjectManagerAwareEntity);
        $this->em->flush();
        $this->em->clear();
    }

    public function testInjectEntityManagerInProxyIfInitializedInUow()
    {
        /* @var $emAware DDC2231EntityManagerAwareEntity|GhostObjectInterface */
        $emAware = $this->em->getReference(
            DDC2231EntityManagerAwareEntity::class,
            $this->persistedEntityManagerAwareEntity->id
        );

        self::assertInstanceOf(GhostObjectInterface::class, $emAware);
        self::assertInstanceOf(DDC2231EntityManagerAwareEntity::class, $emAware);
        self::assertInstanceOf(EntityManagerAware::class, $emAware);
        self::assertFalse($emAware->isProxyInitialized());

        $emAware->initializeProxy();

        self::assertSame($this->em, $emAware->em);
    }

    public function testInjectEntityManagerInFetchedInstance()
    {
        /* @var $emAware DDC2231EntityManagerAwareEntity */
        $emAware = $this->em->find(
            DDC2231EntityManagerAwareEntity::class,
            $this->persistedEntityManagerAwareEntity->id
        );

        self::assertInstanceOf(DDC2231EntityManagerAwareEntity::class, $emAware);
        self::assertInstanceOf(EntityManagerAware::class, $emAware);
        self::assertNotInstanceOf(GhostObjectInterface::class, $emAware);
        self::assertSame($this->em, $emAware->em);
    }

    public function testInjectObjectManagerInProxyIfInitializedInUow()
    {
        /* @var $omAware DDC2231ObjectManagerAwareEntity|GhostObjectInterface */
        $omAware = $this->em->getReference(
            DDC2231ObjectManagerAwareEntity::class,
            $this->persistedObjectManagerAwareEntity->id
        );

        self::assertInstanceOf(GhostObjectInterface::class, $omAware);
        self::assertInstanceOf(DDC2231ObjectManagerAwareEntity::class, $omAware);
        self::assertInstanceOf(ObjectManagerAware::class, $omAware);
        self::assertFalse($omAware->isProxyInitialized());

        $omAware->initializeProxy();

        self::assertSame($this->em, $omAware->em);
    }

    public function testInjectObjectManagerInFetchedInstance()
    {
        /* @var $omAware DDC2231ObjectManagerAwareEntity */
        $omAware = $this->em->find(
            DDC2231ObjectManagerAwareEntity::class,
            $this->persistedObjectManagerAwareEntity->id
        );

        self::assertInstanceOf(DDC2231ObjectManagerAwareEntity::class, $omAware);
        self::assertInstanceOf(ObjectManagerAware::class, $omAware);
        self::assertNotInstanceOf(GhostObjectInterface::class, $omAware);
        self::assertSame($this->em, $omAware->em);
    }
}


/** @ORM\Entity */
class DDC2231EntityManagerAwareEntity implements EntityManagerAware
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @var EntityManagerInterface|null */
    public $em;

    public function injectEntityManager(EntityManagerInterface $entityManager, ClassMetadata $classMetadata) : void
    {
        $this->em = $entityManager;
    }
}


/** @ORM\Entity */
class DDC2231ObjectManagerAwareEntity implements ObjectManagerAware
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @var ObjectManager|null */
    public $om;

    public function injectObjectManager(ObjectManager $objectManager, CommonMetadata $classMetadata) : void
    {
        $this->om = $objectManager;
    }
}
