<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Internal\Hydration\ArrayHydrator;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Tests\Mocks\HydratorMockStatement;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for cascade remove with class table inheritance.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DDC2953Test extends OrmFunctionalTestCase
{
    /**
     * @group DDC-2953
     *
     * When a fetch-join's resultset is not sorted by its root element's identifier,
     * some resultsets were swallowed by hydration.
     */
    public function testMultipleJoinsDoNotBreakArrayHydrationOnMisSortedIndexes()
    {
        $rsmb = new ResultSetMappingBuilder($this->_em);

        $rsmb->addEntityResult(__NAMESPACE__ . '\\DDC2953Foo', 'foo');
        $rsmb->addJoinedEntityResult(__NAMESPACE__ . '\\DDC2953Bar', 'bar', 'foo', 'bar');
        $rsmb->addJoinedEntityResult(__NAMESPACE__ . '\\DDC2953Baz', 'baz', 'bar', 'baz');

        $rsmb->addFieldResult('foo', 'foo_id', 'id');
        $rsmb->addMetaResult('foo', 'bar_id', 'bar');
        $rsmb->addFieldResult('bar', 'bar_id', 'id');
        $rsmb->addMetaResult('baz', 'baz_id', 'bar');
        $rsmb->addFieldResult('baz', 'baz_id', 'id');

        $results = new HydratorMockStatement(array(
            array('foo_id' => 1, 'bar_id' => 1, 'baz_id' => 1),
            array('foo_id' => 1, 'bar_id' => 1, 'baz_id' => 2),
            array('foo_id' => 2, 'bar_id' => 1, 'baz_id' => 3),
            array('foo_id' => 2, 'bar_id' => 1, 'baz_id' => 1),
            array('foo_id' => 2, 'bar_id' => 1, 'baz_id' => 2),
            array('foo_id' => 1, 'bar_id' => 1, 'baz_id' => 3),
        ));

        $hydrator = new ArrayHydrator($this->_em);
        $results  = $hydrator->hydrateAll($results, $rsmb);

        $this->assertCount(2, $results);
        $this->assertCount(3, $results[0]['bar']['baz']);
        $this->assertCount(3, $results[1]['bar']['baz']);
    }
}

/** @Entity */
class DDC2953Foo
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC2953Bar") */
    public $bar;
}

/** @Entity */
class DDC2953Bar
{
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
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC2953Bar", inversedBy="baz") */
    public $bar;
}
