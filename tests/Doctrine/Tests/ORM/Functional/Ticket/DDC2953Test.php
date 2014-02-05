<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Internal\Hydration\ArrayHydrator;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for cascade remove with class table inheritance.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DDC2953Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC2953Foo::CLASSNAME),
            $this->_em->getClassMetadata(DDC2953Bar::CLASSNAME),
            $this->_em->getClassMetadata(DDC2953Baz::CLASSNAME),
        ));
    }

    /**
     * @group DDC-2953
     *
     * When a fetch-join's resultset is not sorted by its root element's identifier,
     * some resultsets were swallowed by hydration.
     */
    public function testMultipleJoinsDoNotBreakArrayHydrationOnMisSortedIndexes()
    {
        /*$foo1 = new DDC2953Foo();
        $foo2 = new DDC2953Foo();
        $bar  = new DDC2953Bar();
        $baz1 = new DDC2953Baz();
        $baz2 = new DDC2953Baz();
        $baz3 = new DDC2953Baz();

        $foo1->bar = $bar;
        $foo2->bar = $bar;

        $bar->baz[] = $baz1;
        $bar->baz[] = $baz2;
        $bar->baz[] = $baz3;
        $baz1->bar  = $bar;
        $baz2->bar  = $bar;
        $baz3->bar  = $bar;

        $this->_em->persist($foo1);
        $this->_em->persist($foo2);
        $this->_em->persist($bar);
        $this->_em->persist($baz1);
        $this->_em->persist($baz2);
        $this->_em->persist($baz3);

        $this->_em->flush();
        $this->_em->clear();

        $results = $this
            ->_em
            ->createQuery(
                'SELECT foo, bar, baz FROM ' . DDC2953Foo::CLASSNAME
                . ' foo LEFT JOIN foo.bar bar LEFT JOIN bar.baz baz'
                . ' ORDER BY bar.id ASC, baz.id ASC, foo.id ASC'
            )->getArrayResult();*/

        $rsmb = new ResultSetMappingBuilder($this->_em);

        $rsmb->addEntityResult(DDC2953Foo::CLASSNAME, 'foo');
        $rsmb->addJoinedEntityResult(DDC2953Bar::CLASSNAME, 'bar', 'foo', 'bar');
        $rsmb->addJoinedEntityResult(DDC2953Baz::CLASSNAME, 'baz', 'bar', 'baz');

        $rsmb->addFieldResult('foo', 'foo_id', 'id');
        $rsmb->addMetaResult('foo', 'bar_id', 'bar');
        $rsmb->addMetaResult('baz', 'baz_id', 'bar');

        $index   = 0;
        $results = array(
            array('foo_id' => 1, 'bar_id' => 1, 'baz_id' => 1),
            array('foo_id' => 1, 'bar_id' => 1, 'baz_id' => 2),
            array('foo_id' => 2, 'bar_id' => 1, 'baz_id' => 3),
            array('foo_id' => 2, 'bar_id' => 1, 'baz_id' => 1),
            array('foo_id' => 2, 'bar_id' => 1, 'baz_id' => 2),
            array('foo_id' => 1, 'bar_id' => 1, 'baz_id' => 3),
        );

        $stmt = $this->getMock('stdClass', array('fetch', 'closeCursor'));

        $stmt->expects($this->any())->method('fetch')->will($this->returnCallback(function () use ($results, & $index) {
            if (isset($results[$index])) {
                $result = $results[$index];

                $index += 1;

                return $result;
            }

            return false;
        }));

        $hydrator = new ArrayHydrator($this->_em);

        $results = $hydrator->hydrateAll($stmt, $rsmb);

        $this->assertCount(2, $results);
        $this->assertCount(3, $results[0]['bar']['baz']);
        $this->assertCount(3, $results[1]['bar']['baz']);
    }
}

/** @Entity */
class DDC2953Foo
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC2953Bar") */
    public $bar;
}

/** @Entity */
class DDC2953Bar
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @OneToMany(targetEntity="DDC2953Baz", mappedBy="bar") */
    public $baz;

    public function __construct()
    {
        $this->baz = new ArrayCollection();
    }
}

/** @Entity */
class DDC2953Baz
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC2953Bar", inversedBy="baz") */
    public $bar;
}
