<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-1514
 */
class DDC1514Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1514EntityA::class),
                $this->em->getClassMetadata(DDC1514EntityB::class),
                $this->em->getClassMetadata(DDC1514EntityC::class),
                ]
            );
        } catch (\Exception $ignored) {
        }
    }

    public function testIssue()
    {
        $a1 = new DDC1514EntityA();
        $a1->title = "1foo";

        $a2 = new DDC1514EntityA();
        $a2->title = "2bar";

        $b1 = new DDC1514EntityB();
        $b1->entityAFrom = $a1;
        $b1->entityATo = $a2;

        $b2 = new DDC1514EntityB();
        $b2->entityAFrom = $a2;
        $b2->entityATo = $a1;

        $c = new DDC1514EntityC();
        $c->title = "baz";
        $a2->entityC = $c;

        $this->em->persist($a1);
        $this->em->persist($a2);
        $this->em->persist($b1);
        $this->em->persist($b2);
        $this->em->persist($c);
        $this->em->flush();
        $this->em->clear();

        $dql = "SELECT a, b, ba, c FROM " . __NAMESPACE__ . "\DDC1514EntityA AS a LEFT JOIN a.entitiesB AS b LEFT JOIN b.entityATo AS ba LEFT JOIN a.entityC AS c ORDER BY a.title";
        $results = $this->em->createQuery($dql)->getResult();

        self::assertEquals($a1->id, $results[0]->id);
        self::assertNull($results[0]->entityC);

        self::assertEquals($a2->id, $results[1]->id);
        self::assertEquals($c->title, $results[1]->entityC->title);
    }
}

/**
 * @Entity
 */
class DDC1514EntityA
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column */
    public $title;
    /** @ManyToMany(targetEntity="DDC1514EntityB", mappedBy="entityAFrom") */
    public $entitiesB;
    /** @ManyToOne(targetEntity="DDC1514EntityC") */
    public $entityC;

    public function __construct()
    {
        $this->entitiesB = new ArrayCollection();
    }
}

/**
 * @Entity
 */
class DDC1514EntityB
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC1514EntityA", inversedBy="entitiesB")
     */
    public $entityAFrom;
    /**
     * @ManyToOne(targetEntity="DDC1514EntityA")
     */
    public $entityATo;
}

/**
 * @Entity
 */
class DDC1514EntityC
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column */
    public $title;
}
