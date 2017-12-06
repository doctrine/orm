<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerAware;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @group DDC-2231
 */
final class DDC2231Test extends OrmFunctionalTestCase
{
    /**
     * @var DDC2231EntityManagerAwareEntity
     */
    private $persistedEntityManagerAwareEntity;

    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema([
                $this->em->getClassMetadata(DDC2231EntityManagerAwareEntity::class),
            ]);
        } catch (ToolsException $ignored) {
            // ignore - schema already exists
        }

        $this->persistedEntityManagerAwareEntity = new DDC2231EntityManagerAwareEntity();

        $this->em->persist($this->persistedEntityManagerAwareEntity);
        $this->em->flush();
        $this->em->clear();
    }

    public function testInjectEntityManagerInProxyIfInitializedInUow() : void
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
        self::assertSame($this->em, $emAware->em);

        $emAware->initializeProxy();

        self::assertSame($this->em, $emAware->em);
    }

    public function testInjectEntityManagerInFetchedInstance() : void
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
