<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC199Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC199ParentClass::class),
                $this->em->getClassMetadata(DDC199ChildClass::class),
                $this->em->getClassMetadata(DDC199RelatedClass::class),
            ]
        );
    }

    public function testPolymorphicLoading() : void
    {
        $child             = new DDC199ChildClass();
        $child->parentData = 'parentData';
        $child->childData  = 'childData';
        $this->em->persist($child);

        $related1              = new DDC199RelatedClass();
        $related1->relatedData = 'related1';
        $related1->parent      = $child;
        $this->em->persist($related1);

        $related2              = new DDC199RelatedClass();
        $related2->relatedData = 'related2';
        $related2->parent      = $child;
        $this->em->persist($related2);

        $this->em->flush();
        $this->em->clear();

        $query  = $this->em->createQuery('select e,r from Doctrine\Tests\ORM\Functional\Ticket\DDC199ParentClass e join e.relatedEntities r');
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
 * @ORM\Entity @ORM\Table(name="ddc199_entities")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"parent" = DDC199ParentClass::class, "child" = DDC199ChildClass::class})
 */
class DDC199ParentClass
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $parentData;

    /** @ORM\OneToMany(targetEntity=DDC199RelatedClass::class, mappedBy="parent") */
    public $relatedEntities;
}


/** @ORM\Entity */
class DDC199ChildClass extends DDC199ParentClass
{
    /** @ORM\Column */
    public $childData;
}

/** @ORM\Entity @ORM\Table(name="ddc199_relatedclass") */
class DDC199RelatedClass
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\Column */
    public $relatedData;

    /**
     * @ORM\ManyToOne(targetEntity=DDC199ParentClass::class, inversedBy="relatedEntities")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    public $parent;
}
