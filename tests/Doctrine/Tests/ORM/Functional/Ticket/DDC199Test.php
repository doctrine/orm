<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC199Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC199ParentClass'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC199ChildClass'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC199RelatedClass')
        ));
    }

    public function testPolymorphicLoading()
    {
        $child = new DDC199ChildClass;
        $child->parentData = 'parentData';
        $child->childData = 'childData';
        $this->_em->persist($child);

        $related1 = new DDC199RelatedClass;
        $related1->relatedData = 'related1';
        $related1->parent = $child;
        $this->_em->persist($related1);

        $related2 = new DDC199RelatedClass;
        $related2->relatedData = 'related2';
        $related2->parent = $child;
        $this->_em->persist($related2);

        $this->_em->flush();
        $this->_em->clear();

        $query = $this->_em->createQuery('select e,r from Doctrine\Tests\ORM\Functional\Ticket\DDC199ParentClass e join e.relatedEntities r');
        $result = $query->getResult();

        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Ticket\DDC199ParentClass', $result[0]);
        $this->assertTrue($result[0]->relatedEntities->isInitialized());
        $this->assertEquals(2, $result[0]->relatedEntities->count());
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Ticket\DDC199RelatedClass', $result[0]->relatedEntities[0]);
        $this->assertInstanceOf('Doctrine\Tests\ORM\Functional\Ticket\DDC199RelatedClass', $result[0]->relatedEntities[1]);
    }
}


/**
 * @Entity @Table(name="ddc199_entities")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"parent" = "DDC199ParentClass", "child" = "DDC199ChildClass"})
 */
class DDC199ParentClass
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $parentData;

    /**
     * @OneToMany(targetEntity="DDC199RelatedClass", mappedBy="parent")
     */
    public $relatedEntities;
}


/** @Entity */
class DDC199ChildClass extends DDC199ParentClass
{
    /**
     * @Column
     */
    public $childData;
}

/** @Entity @Table(name="ddc199_relatedclass") */
class DDC199RelatedClass
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column */
    public $relatedData;

    /**
     * @ManyToOne(targetEntity="DDC199ParentClass", inversedBy="relatedEntities")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
    public $parent;
}