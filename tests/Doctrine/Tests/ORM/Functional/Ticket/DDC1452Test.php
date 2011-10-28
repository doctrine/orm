<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1452
 */
class DDC1452Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1452EntityA'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1452EntityB'),
            ));
        } catch (\Exception $ignored) {
        }
    }

    public function testIssue()
    {
        $a = new DDC1452EntityA();
        $a->title = "foo";

        $b = new DDC1452EntityB();
        $b->entityAFrom = $a;
        $b->entityATo = $a;

        $this->_em->persist($a);
        $this->_em->persist($b);
        $this->_em->flush();
        $this->_em->clear();

        $dql = "SELECT a, b, ba FROM " . __NAMESPACE__ . "\DDC1452EntityA AS a LEFT JOIN a.entitiesB AS b LEFT JOIN b.entityATo AS ba";
        $results = $this->_em->createQuery($dql)->getResult();

        $this->assertSame($results[0], $results[0]->entitiesB[0]->entityAFrom);
        $this->assertSame($results[0], $results[0]->entitiesB[0]->entityATo);
    }
}

/**
 * @Entity
 */
class DDC1452EntityA
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
    /** @Column */
    public $title;
    /** @ManyToMany(targetEntity="DDC1452EntityB", mappedBy="entityAFrom") */
    public $entitiesB;
}

/**
 * @Entity
 */
class DDC1452EntityB
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC1452EntityA", inversedBy="entitiesB")
     */
    public $entityAFrom;
    /**
     * @ManyToOne(targetEntity="DDC1452EntityA")
     */
    public $entityATo;
}