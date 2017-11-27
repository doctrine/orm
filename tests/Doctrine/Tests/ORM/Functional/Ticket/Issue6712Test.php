<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @group issue-6712
 */
class Issue6712Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * Sorting by scalar values works as expected.
     *
     * @return void
     */
    public function testSortingCollectionByScalar()
    {
        $a = new \stdClass();
        $a->foo = 10;
        $a->bar = 400;

        $b = new \stdClass();
        $b->foo = 20;
        $b->bar = 100;

        $c = new \stdClass();
        $c->foo = 20;
        $c->bar = 300;

        $d = new \stdClass();
        $d->foo = 20;
        $d->bar = 200;

        $collection = new ArrayCollection([$a, $b, $c, $d]);

        $sortedCollection = $collection->matching(
            new Criteria(null, ['foo' => Criteria::ASC, 'bar' => Criteria::ASC])
        );

        $this->assertEquals(
            [0 => $a, 1 => $b, 3 => $d, 2 => $c],
            $sortedCollection->toArray()
        );
    }

    /**
     * Sorting by date values does not work as expected.
     *
     * $next() will only be evaluated, if $foo1 and $foo2 are identical.
     *
     * @return void
     */
    public function testSortingCollectionByDateTimeObjects()
    {
        $foo1 = new \DateTime('yesterday');
        $foo2 = new \DateTime('now');

        $a = new \stdClass();
        $a->foo = clone $foo1;
        $a->bar = 400;

        $b = new \stdClass();
        $b->foo = clone $foo2;
        $b->bar = 100;

        $c = new \stdClass();
        $c->foo = clone $foo2;
        $c->bar = 300;

        $d = new \stdClass();
        $d->foo = clone $foo2;
        $d->bar = 200;

        $collection = new ArrayCollection([$a, $b, $c, $d]);

        $sortedCollection = $collection->matching(
            new Criteria(null, ['foo' => Criteria::ASC, 'bar' => Criteria::ASC])
        );

        $this->assertSame(
            [0 => $a, 1 => $b, 3 => $d, 2 => $c],
            $sortedCollection->toArray()
        );
    }

    /**
     * Sorting by object values does not work as expected, either.
     *
     * $next() will only be evaluated, if $foo1 and $foo2 are identical.
     *
     * @return void
     */
    public function testSortingCollectionByObjects()
    {
        $foo1 = new \stdClass();
        $foo1->value = 1;

        $foo2 = new \stdClass();
        $foo2->value = 2;

        $a = new \stdClass();
        $a->foo = clone $foo1;
        $a->bar = 400;

        $b = new \stdClass();
        $b->foo = clone $foo2;
        $b->bar = 100;

        $c = new \stdClass();
        $c->foo = clone $foo2;
        $c->bar = 300;

        $d = new \stdClass();
        $d->foo = clone $foo2;
        $d->bar = 200;

        $collection = new ArrayCollection([$a, $b, $c, $d]);

        $sortedCollection = $collection->matching(
            new Criteria(null, ['foo' => Criteria::ASC, 'bar' => Criteria::ASC])
        );

        $this->assertSame(
            [0 => $a, 1 => $b, 3 => $d, 2 => $c],
            $sortedCollection->toArray()
        );
    }
}
