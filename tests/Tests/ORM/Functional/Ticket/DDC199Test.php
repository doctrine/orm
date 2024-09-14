<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC199Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC199ParentClass::class,
            DDC199ChildClass::class,
            DDC199RelatedClass::class
        );
    }

    public function testPolymorphicLoading(): void
    {
        $child             = new DDC199ChildClass();
        $child->parentData = 'parentData';
        $child->childData  = 'childData';
        $this->_em->persist($child);

        $related1              = new DDC199RelatedClass();
        $related1->relatedData = 'related1';
        $related1->parent      = $child;
        $this->_em->persist($related1);

        $related2              = new DDC199RelatedClass();
        $related2->relatedData = 'related2';
        $related2->parent      = $child;
        $this->_em->persist($related2);

        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('select e,r from Doctrine\Tests\ORM\Functional\Ticket\DDC199ParentClass e join e.relatedEntities r');
        $result = $query->getResult();

        self::assertCount(1, $result);
        self::assertInstanceOf(DDC199ParentClass::class, $result[0]);
        self::assertTrue($result[0]->relatedEntities->isInitialized());
        self::assertEquals(2, $result[0]->relatedEntities->count());
        self::assertInstanceOf(DDC199RelatedClass::class, $result[0]->relatedEntities[0]);
        self::assertInstanceOf(DDC199RelatedClass::class, $result[0]->relatedEntities[1]);
    }
}


/**
 * @Entity
 * @Table(name="ddc199_entities")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"parent" = "DDC199ParentClass", "child" = "DDC199ChildClass"})
 */
class DDC199ParentClass
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    public $parentData;

    /**
     * @psalm-var Collection<int, DDC199RelatedClass>
     * @OneToMany(targetEntity="DDC199RelatedClass", mappedBy="parent")
     */
    public $relatedEntities;
}


/** @Entity */
class DDC199ChildClass extends DDC199ParentClass
{
    /**
     * @var string
     * @Column
     */
    public $childData;
}

/**
 * @Entity
 * @Table(name="ddc199_relatedclass")
 */
class DDC199RelatedClass
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column
     */
    public $relatedData;

    /**
     * @var DDC199ParentClass
     * @ManyToOne(targetEntity="DDC199ParentClass", inversedBy="relatedEntities")
     * @JoinColumn(name="parent_id", referencedColumnName="id")
     */
    public $parent;
}
