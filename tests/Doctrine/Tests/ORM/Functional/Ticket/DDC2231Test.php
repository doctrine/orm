<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\EntityManagerAware;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @group DDC-2231
 */
class DDC2231Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(DDC2231EntityY::class),
        ]);
    }

    public function testInjectObjectManagerInProxyIfInitializedInUow()
    {
        $y1 = new DDC2231EntityY;

        $this->em->persist($y1);

        $this->em->flush();
        $this->em->clear();

        /* @var $y1ref DDC2231EntityY|GhostObjectInterface */
        $y1ref = $this->em->getReference(DDC2231EntityY::class, $y1->id);

        self::assertInstanceOf(GhostObjectInterface::class, $y1ref);
        self::assertInstanceOf(DDC2231EntityY::class, $y1ref);
        self::assertFalse($y1ref->isProxyInitialized());

        $y1ref->initializeProxy();

        self::assertSame($this->em, $y1ref->om);
    }
}


/** @ORM\Entity @ORM\Table(name="ddc2231_y") */
class DDC2231EntityY implements EntityManagerAware
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     */
    public $id;

    public $om;

    public function injectEntityManager(EntityManagerInterface $objectManager, ClassMetadata $classMetadata) : void
    {
        $this->om = $objectManager;
    }

    public function getId()
    {
        return $this->id;
    }

    public function doSomething()
    {
    }
}
