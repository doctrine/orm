<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\Common\Persistence\ObjectManagerAware;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-2231
 */
class DDC2231Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2231EntityY'),
        ));
    }

    public function testInjectObjectManagerInProxyIfInitializedInUow()
    {
        $y1 = new DDC2231EntityY;

        $this->_em->persist($y1);

        $this->_em->flush();
        $this->_em->clear();

        $y1ref = $this->_em->getReference(get_class($y1), $y1->id);

        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $y1ref);
        $this->assertFalse($y1ref->__isInitialized__);

        $id = $y1ref->doSomething();

        $this->assertTrue($y1ref->__isInitialized__);
        $this->assertEquals($this->_em, $y1ref->om);
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
