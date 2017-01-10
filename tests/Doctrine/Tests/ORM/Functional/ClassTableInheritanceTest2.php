<?php

namespace Doctrine\Tests\ORM\Functional;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 *
 * @author robo
 */
class ClassTableInheritanceTest2 extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(CTIParent::class),
                $this->em->getClassMetadata(CTIChild::class),
                $this->em->getClassMetadata(CTIRelated::class),
                $this->em->getClassMetadata(CTIRelated2::class)
                ]
            );
        } catch (\Exception $ignored) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testOneToOneAssocToBaseTypeBidirectional()
    {
        $child = new CTIChild;
        $child->setData('hello');

        $related = new CTIRelated;
        $related->setCTIParent($child);

        $this->em->persist($related);
        $this->em->persist($child);

        $this->em->flush();
        $this->em->clear();

        $relatedId = $related->getId();

        $related2 = $this->em->find(CTIRelated::class, $relatedId);

        self::assertInstanceOf(CTIRelated::class, $related2);
        self::assertInstanceOf(CTIChild::class, $related2->getCTIParent());
        self::assertNotInstanceOf(Proxy::class, $related2->getCTIParent());
        self::assertEquals('hello', $related2->getCTIParent()->getData());

        self::assertSame($related2, $related2->getCTIParent()->getRelated());
    }

    public function testManyToManyToCTIHierarchy()
    {
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        $mmrel = new CTIRelated2;
        $child = new CTIChild;
        $child->setData('child');
        $mmrel->addCTIChild($child);

        $this->em->persist($mmrel);
        $this->em->persist($child);

        $this->em->flush();
        $this->em->clear();

        $mmrel2 = $this->em->find(get_class($mmrel), $mmrel->getId());
        self::assertFalse($mmrel2->getCTIChildren()->isInitialized());
        self::assertEquals(1, count($mmrel2->getCTIChildren()));
        self::assertTrue($mmrel2->getCTIChildren()->isInitialized());
        self::assertInstanceOf(CTIChild::class, $mmrel2->getCTIChildren()->get(0));
    }
}

/**
 * @Entity @Table(name="cti_parents")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"parent" = "CTIParent", "child" = "CTIChild"})
 */
class CTIParent {
   /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @OneToOne(targetEntity="CTIRelated", mappedBy="ctiParent") */
    private $related;

    public function getId() {
        return $this->id;
    }

    public function getRelated() {
        return $this->related;
    }

    public function setRelated($related) {
        $this->related = $related;
        $related->setCTIParent($this);
    }
}

/**
 * @Entity @Table(name="cti_children")
 */
class CTIChild extends CTIParent {
   /**
     * @Column(type="string")
     */
    private $data;

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }

}

/** @Entity */
class CTIRelated {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="CTIParent")
     * @JoinColumn(name="ctiparent_id", referencedColumnName="id")
     */
    private $ctiParent;

    public function getId() {
        return $this->id;
    }

    public function getCTIParent() {
        return $this->ctiParent;
    }

    public function setCTIParent($ctiParent) {
        $this->ctiParent = $ctiParent;
    }
}

/** @Entity */
class CTIRelated2
{
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @ManyToMany(targetEntity="CTIChild") */
    private $ctiChildren;


    public function __construct()
    {
        $this->ctiChildren = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addCTIChild(CTIChild $child)
    {
        $this->ctiChildren->add($child);
    }

    public function getCTIChildren()
    {
        return $this->ctiChildren;
    }
}
