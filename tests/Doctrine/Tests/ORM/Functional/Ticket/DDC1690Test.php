<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ORM\Proxy\Proxy;

class DDC1690Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC1690Parent::class),
                $this->_em->getClassMetadata(DDC1690Child::class)
                ]
            );
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testChangeTracking()
    {
        $parent = new DDC1690Parent();
        $child = new DDC1690Child();
        $parent->setName('parent');
        $child->setName('child');

        $parent->setChild($child);
        $child->setParent($parent);

        $this->_em->persist($parent);
        $this->_em->persist($child);

        $this->assertEquals(1, count($parent->listeners));
        $this->assertEquals(1, count($child->listeners));

        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals(1, count($parent->listeners));
        $this->assertEquals(1, count($child->listeners));

        $parentId = $parent->getId();
        $childId = $child->getId();
        unset($parent, $child);

        $parent = $this->_em->find(DDC1690Parent::class, $parentId);
        $child = $this->_em->find(DDC1690Child::class, $childId);

        $this->assertEquals(1, count($parent->listeners));
        $this->assertInstanceOf(Proxy::class, $child, 'Verifying that $child is a proxy before using proxy API');
        $this->assertCount(0, $child->listeners);
        $child->__load();
        $this->assertCount(1, $child->listeners);
        unset($parent, $child);

        $parent = $this->_em->find(DDC1690Parent::class, $parentId);
        $child = $parent->getChild();

        $this->assertEquals(1, count($parent->listeners));
        $this->assertEquals(1, count($child->listeners));
        unset($parent, $child);

        $child = $this->_em->find(DDC1690Child::class, $childId);
        $parent = $child->getParent();

        $this->assertEquals(1, count($parent->listeners));
        $this->assertEquals(1, count($child->listeners));
    }
}

class NotifyBaseEntity implements NotifyPropertyChanged {
    public $listeners = [];

    public function addPropertyChangedListener(PropertyChangedListener $listener) {
        if (!in_array($listener, $this->listeners)) {
            $this->listeners[] = $listener;
        }
    }

    protected function onPropertyChanged($propName, $oldValue, $newValue) {
        if ($this->listeners) {
            foreach ($this->listeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }
    }
}

/** @Entity @ChangeTrackingPolicy("NOTIFY") */
class DDC1690Parent extends NotifyBaseEntity {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /** @Column */
    private $name;

    /** @OneToOne(targetEntity="DDC1690Child") */
    private $child;

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function setName($name) {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    function setChild($child) {
        $this->child = $child;
    }

    function getChild() {
        return $this->child;
    }
}

/** @Entity */
class DDC1690Child extends NotifyBaseEntity {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /** @Column */
    private $name;

    /** @OneToOne(targetEntity="DDC1690Parent", mappedBy="child") */
    private $parent;

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function setName($name) {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    function setParent($parent) {
        $this->parent = $parent;
    }

    function getParent() {
        return $this->parent;
    }
}
