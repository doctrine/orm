<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectManagerAware;
use Doctrine\ORM\Proxy\Proxy;

/**
 * @group DDC-2231
 */
class DDC2231Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC2231EntityY::class),
            ]
        );
    }

    public function testInjectObjectManagerInProxyIfInitializedInUow()
    {
        $y1 = new DDC2231EntityY;

        $this->em->persist($y1);

        $this->em->flush();
        $this->em->clear();

        $y1ref = $this->em->getReference(get_class($y1), $y1->id);

        self::assertInstanceOf(Proxy::class, $y1ref);
        self::assertFalse($y1ref->__isInitialized__);

        $id = $y1ref->doSomething();

        self::assertTrue($y1ref->__isInitialized__);
        self::assertEquals($this->em, $y1ref->om);
    }
}


/** @Entity @Table(name="ddc2231_y") */
class DDC2231EntityY implements ObjectManagerAware
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;

    public $om;

    public function injectObjectManager(ObjectManager $objectManager, ClassMetadata $classMetadata)
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
