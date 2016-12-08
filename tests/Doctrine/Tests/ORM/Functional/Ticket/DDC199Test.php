<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC199Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC199ParentClass::class),
            $this->_em->getClassMetadata(DDC199ChildClass::class),
            $this->_em->getClassMetadata(DDC199RelatedClass::class)
            ]
        );
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
        $this->assertInstanceOf(DDC199ParentClass::class, $result[0]);
        $this->assertTrue($result[0]->relatedEntities->isInitialized());
        $this->assertEquals(2, $result[0]->relatedEntities->count());
        $this->assertInstanceOf(DDC199RelatedClass::class, $result[0]->relatedEntities[0]);
        $this->assertInstanceOf(DDC199RelatedClass::class, $result[0]->relatedEntities[1]);
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
