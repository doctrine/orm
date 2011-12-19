<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC729Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->_em);
            $schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC729A'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC729B'),
            ));
        } catch(\Exception $e) {

        }
    }

    public function testMergeManyToMany()
    {
        $a = new DDC729A();
        $b = new DDC729B();
        $a->related[] = $b;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->flush();
        $this->_em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $a->related);

        $a = $this->_em->merge($a);

        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $a->related);

        $this->assertFalse($a->related->isInitialized(), "Collection should not be marked initialized.");
        $this->assertFalse($a->related->isDirty(), "Collection should not be marked as dirty.");

        $this->_em->flush();
        $this->_em->clear();

        $a = $this->_em->find(__NAMESPACE__ . '\DDC729A', $aId);
        $this->assertEquals(1, count($a->related));
    }

    public function testUnidirectionalMergeManyToMany()
    {
        $a = new DDC729A();
        $b1 = new DDC729B();
        $b2 = new DDC729B();
        $a->related[] = $b1;

        $this->_em->persist($a);
        $this->_em->persist($b1);
        $this->_em->persist($b2);
        $this->_em->flush();
        $this->_em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        $a = $this->_em->merge($a);

        $a->related->set(0, $this->_em->merge($b1));

        $a->related->set(1, $this->_em->merge($b2));

        $this->_em->flush();
        $this->_em->clear();

        $a = $this->_em->find(__NAMESPACE__ . '\DDC729A', $aId);
        $this->assertEquals(2, count($a->related));
    }

    public function testBidirectionalMergeManyToMany()
    {
        $a = new DDC729A();
        $b1 = new DDC729B();
        $b2 = new DDC729B();
        $a->related[] = $b1;

        $this->_em->persist($a);
        $this->_em->persist($b1);
        $this->_em->persist($b2);
        $this->_em->flush();
        $this->_em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        $a = $this->_em->merge($a);

        $a->related->set(0, $this->_em->merge($b1));
        $b1->related->set(0, $a);

        $a->related->set(1, $this->_em->merge($b2));
        $b2->related->set(0, $a);

        $this->_em->flush();
        $this->_em->clear();

        $a = $this->_em->find(__NAMESPACE__ . '\DDC729A', $aId);
        $this->assertEquals(2, count($a->related));
    }

    public function testBidirectionalMultiMergeManyToMany()
    {
        $a = new DDC729A();
        $b1 = new DDC729B();
        $b2 = new DDC729B();
        $a->related[] = $b1;

        $this->_em->persist($a);
        $this->_em->persist($b1);
        $this->_em->persist($b2);
        $this->_em->flush();
        $this->_em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        $a = $this->_em->merge($a);

        $a->related->set(0, $this->_em->merge($b1));
        $b1->related->set(0, $this->_em->merge($a));

        $a->related->set(1, $this->_em->merge($b2));
        $b2->related->set(0, $this->_em->merge($a));

        $this->_em->flush();
        $this->_em->clear();

        $a = $this->_em->find(__NAMESPACE__ . '\DDC729A', $aId);
        $this->assertEquals(2, count($a->related));
    }
}

/**
 * @Entity
 */
class DDC729A
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /** @ManyToMany(targetEntity="DDC729B", inversedBy="related") */
    public $related;

    public function __construct()
    {
        $this->related = new \Doctrine\Common\Collections\ArrayCollection();
    }
}

/**
 * @Entity
 */
class DDC729B
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /** @ManyToMany(targetEntity="DDC729B", mappedBy="related") */
    public $related;

    public function __construct()
    {
        $this->related = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
