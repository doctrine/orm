<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1514
 */
class DDC1514Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1514EntityA'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1514EntityB'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1514EntityC'),
            ));
        } catch (\Exception $ignored) {
        }
    }

    public function testIssue()
    {
        $a1 = new DDC1514EntityA();
        $a1->title = "foo";

        $a2 = new DDC1514EntityA();
        $a2->title = "bar";

        $b1 = new DDC1514EntityB();
        $b1->entityAFrom = $a1;
        $b1->entityATo = $a2;

        $b2 = new DDC1514EntityB();
        $b2->entityAFrom = $a2;
        $b2->entityATo = $a1;

        $c = new DDC1514EntityC();
        $c->title = "baz";
        $a2->entityC = $c;

        $this->_em->persist($a1);
        $this->_em->persist($a2);
        $this->_em->persist($b1);
        $this->_em->persist($b2);
        $this->_em->persist($c);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT a, b, ba, c FROM " . __NAMESPACE__ . "\DDC1514EntityA AS a LEFT JOIN a.entitiesB AS b LEFT JOIN b.entityATo AS ba LEFT JOIN a.entityC AS c";
        $results = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals($c->title, $results[1]->entityC->title);
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
