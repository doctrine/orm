<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

class DDC729Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
            $schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC729A::class),
                $this->em->getClassMetadata(DDC729B::class),
                ]
            );
        } catch(\Exception $e) {

        }
    }

    public function testMergeManyToMany()
    {
        $a = new DDC729A();
        $b = new DDC729B();
        $a->related[] = $b;

        $this->em->persist($a);
        $this->em->persist($b);
        $this->em->flush();
        $this->em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        self::assertInstanceOf(ArrayCollection::class, $a->related);

        $a = $this->em->merge($a);

        self::assertInstanceOf(PersistentCollection::class, $a->related);

        self::assertFalse($a->related->isInitialized(), "Collection should not be marked initialized.");
        self::assertFalse($a->related->isDirty(), "Collection should not be marked as dirty.");

        $this->em->flush();
        $this->em->clear();

        $a = $this->em->find(DDC729A::class, $aId);
        self::assertEquals(1, count($a->related));
    }

    public function testUnidirectionalMergeManyToMany()
    {
        $a = new DDC729A();
        $b1 = new DDC729B();
        $b2 = new DDC729B();
        $a->related[] = $b1;

        $this->em->persist($a);
        $this->em->persist($b1);
        $this->em->persist($b2);
        $this->em->flush();
        $this->em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        $a = $this->em->merge($a);

        $a->related->set(0, $this->em->merge($b1));

        $a->related->set(1, $this->em->merge($b2));

        $this->em->flush();
        $this->em->clear();

        $a = $this->em->find(DDC729A::class, $aId);
        self::assertEquals(2, count($a->related));
    }

    public function testBidirectionalMergeManyToMany()
    {
        $a = new DDC729A();
        $b1 = new DDC729B();
        $b2 = new DDC729B();
        $a->related[] = $b1;

        $this->em->persist($a);
        $this->em->persist($b1);
        $this->em->persist($b2);
        $this->em->flush();
        $this->em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        $a = $this->em->merge($a);

        $a->related->set(0, $this->em->merge($b1));
        $b1->related->set(0, $a);

        $a->related->set(1, $this->em->merge($b2));
        $b2->related->set(0, $a);

        $this->em->flush();
        $this->em->clear();

        $a = $this->em->find(DDC729A::class, $aId);
        self::assertEquals(2, count($a->related));
    }

    public function testBidirectionalMultiMergeManyToMany()
    {
        $a = new DDC729A();
        $b1 = new DDC729B();
        $b2 = new DDC729B();
        $a->related[] = $b1;

        $this->em->persist($a);
        $this->em->persist($b1);
        $this->em->persist($b2);
        $this->em->flush();
        $this->em->clear();
        $aId = $a->id;

        $a = new DDC729A();
        $a->id = $aId;

        $a = $this->em->merge($a);

        $a->related->set(0, $this->em->merge($b1));
        $b1->related->set(0, $this->em->merge($a));

        $a->related->set(1, $this->em->merge($b2));
        $b2->related->set(0, $this->em->merge($a));

        $this->em->flush();
        $this->em->clear();

        $a = $this->em->find(DDC729A::class, $aId);
        self::assertEquals(2, count($a->related));
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
