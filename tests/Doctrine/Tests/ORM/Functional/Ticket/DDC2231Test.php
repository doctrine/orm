<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Doctrine\Common\Persistence\ObjectManager;

use Doctrine\Common\Persistence\ObjectManagerAware;

require_once __DIR__ . '/../../../TestInit.php';

class DDC237Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2231EntityX'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2231EntityY'),
        ));
    }

    public function testInjectObjectManagerInProxyIfInitializedInUow()
    {
        $_x  = new DDC2231EntityX;
        $_y1 = new DDC2231EntityY;
        $_y2 = new DDC2231EntityY;

        $_x->data = 'X';
        $_y1->data = 'Y1';
        $_y2->data = 'Y2';

        $_x->y = array($_y1, $_y2);
        $_y1->x = $_x;
        $_y2->x = $_x;

        $this->_em->persist($_x);
        $this->_em->persist($_y1);
        $this->_em->persist($_y2);

        $this->_em->flush();
        $this->_em->clear();

        $x = $this->_em->find(get_class($_x), $_x->id);
        $y1ref = $this->_em->getReference(get_class($_y1), $_y1->id);

        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $y1ref);
        $this->assertFalse($y1ref->__isInitialized__);

        $y1 = $x->y->get(0);
        $this->assertEquals($y1->data, $_y1->data);
        $this->assertEquals($this->_em, $y1->om);

        $y2 = $x->y->get(1);
        $this->assertEquals($y2->data, $_y2->data);
        $this->assertEquals($this->_em, $y2->om);
    }
}


/**
 * @Entity @Table(name="ddc2231_x")
 */
class DDC2231EntityX
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;
    /**
     * @Column(type="string")
     */
    public $data;
    /**
     * @OneToMany(targetEntity="DDC2231EntityY", mappedBy="x")
     */
    public $y;
}


/** @Entity @Table(name="ddc2231_y") */
class DDC2231EntityY implements ObjectManagerAware
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     */
    public $id;
    /**
     * @Column(type="string")
     */
    public $data;
    /**
     * @ManyToOne(targetEntity="DDC2231EntityX", inversedBy="y")
     **/
    public $x;

    public $om;

    public function injectObjectManager(ObjectManager $objectManager, ClassMetadata $classMetadata) {
        $this->om = $objectManager;
    }
}