<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 *
 * @covers \Doctrine\ORM\LazyCriteriaCollection
 */
class LazyCriteriaCollectionTest extends TestCase
{
    /**
     * @var \Doctrine\ORM\Persisters\Entity\EntityPersister|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persister;

    /**
     * @var Criteria
     */
    private $criteria;

    /**
     * @var LazyCriteriaCollection
     */
    private $lazyCriteriaCollection;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->persister              = $this->createMock(EntityPersister::class);
        $this->criteria               = new Criteria();
        $this->lazyCriteriaCollection = new LazyCriteriaCollection($this->persister, $this->criteria);
    }

    public function testCountIsCached()
    {
        $this->persister->expects($this->once())->method('count')->with($this->criteria)->will($this->returnValue(10));

        $this->assertSame(10, $this->lazyCriteriaCollection->count());
        $this->assertSame(10, $this->lazyCriteriaCollection->count());
        $this->assertSame(10, $this->lazyCriteriaCollection->count());
    }

    public function testCountIsCachedEvenWithZeroResult()
    {
        $this->persister->expects($this->once())->method('count')->with($this->criteria)->will($this->returnValue(0));

        $this->assertSame(0, $this->lazyCriteriaCollection->count());
        $this->assertSame(0, $this->lazyCriteriaCollection->count());
        $this->assertSame(0, $this->lazyCriteriaCollection->count());
    }

    public function testCountUsesWrappedCollectionWhenInitialized()
    {
        $this
            ->persister
            ->expects($this->once())
            ->method('loadCriteria')
            ->with($this->criteria)
            ->will($this->returnValue(['foo', 'bar', 'baz']));

        // should never call the persister's count
        $this->persister->expects($this->never())->method('count');

        $this->assertSame(['foo', 'bar', 'baz'], $this->lazyCriteriaCollection->toArray());

        $this->assertSame(3, $this->lazyCriteriaCollection->count());
    }

    public function testMatchingUsesThePersisterOnlyOnce()
    {
        $foo = new stdClass();
        $bar = new stdClass();
        $baz = new stdClass();

        $foo->val = 'foo';
        $bar->val = 'bar';
        $baz->val = 'baz';

        $this
            ->persister
            ->expects($this->once())
            ->method('loadCriteria')
            ->with($this->criteria)
            ->will($this->returnValue([$foo, $bar, $baz]));

        $criteria = new Criteria();

        $criteria->andWhere($criteria->expr()->eq('val', 'foo'));

        $filtered = $this->lazyCriteriaCollection->matching($criteria);

        $this->assertInstanceOf(Collection::class, $filtered);
        $this->assertEquals([$foo], $filtered->toArray());

        $this->assertEquals([$foo], $this->lazyCriteriaCollection->matching($criteria)->toArray());
    }

    public function testIsEmptyUsesCountWhenNotInitialized()
    {
        $this->persister->expects($this->once())->method('count')->with($this->criteria)->will($this->returnValue(0));

        $this->assertTrue($this->lazyCriteriaCollection->isEmpty());
    }

    public function testIsEmptyIsFalseIfCountIsNotZero()
    {
        $this->persister->expects($this->once())->method('count')->with($this->criteria)->will($this->returnValue(1));

        $this->assertFalse($this->lazyCriteriaCollection->isEmpty());
    }

    public function testIsEmptyUsesWrappedCollectionWhenInitialized()
    {
        $this
            ->persister
            ->expects($this->once())
            ->method('loadCriteria')
            ->with($this->criteria)
            ->will($this->returnValue(['foo', 'bar', 'baz']));

        // should never call the persister's count
        $this->persister->expects($this->never())->method('count');

        $this->assertSame(['foo', 'bar', 'baz'], $this->lazyCriteriaCollection->toArray());

        $this->assertFalse($this->lazyCriteriaCollection->isEmpty());
    }
}
