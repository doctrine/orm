<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use stdClass;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 *
 * @covers \Doctrine\ORM\LazyCriteriaCollection
 */
class LazyCriteriaCollectionTest extends \PHPUnit_Framework_TestCase
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
            ->will($this->returnValue(array('foo', 'bar', 'baz')));

        // should never call the persister's count
        $this->persister->expects($this->never())->method('count');

        $this->assertSame(array('foo', 'bar', 'baz'), $this->lazyCriteriaCollection->toArray());

        $this->assertSame(3, $this->lazyCriteriaCollection->count());
    }

    public function testMatchingUsesThePersisterOnlyOnce()
    {
        $foo = new stdClass();

        $foo->val = 'foo';

        $criteria = new Criteria();

        $criteria->andWhere($criteria->expr()->eq('val', 'foo'));

        $this
            ->persister
            ->expects($this->once())
            ->method('loadCriteria')
            ->with($criteria)
            ->will($this->returnValue(array($foo)));

        $filtered = $this->lazyCriteriaCollection->matching($criteria);

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $filtered);
        $this->assertEquals(array($foo), $filtered->toArray());
        $this->assertEquals(array($foo), $filtered->matching($criteria)->toArray());
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
            ->will($this->returnValue(array('foo', 'bar', 'baz')));

        // should never call the persister's count
        $this->persister->expects($this->never())->method('count');

        $this->assertSame(array('foo', 'bar', 'baz'), $this->lazyCriteriaCollection->toArray());

        $this->assertFalse($this->lazyCriteriaCollection->isEmpty());
    }

    public function testCanMatchTwiceWithoutInitializing()
    {
        $foo = new stdClass();

        $foo->val = 'foo';

        $criteria = new Criteria();

        $criteria->andWhere($criteria->expr()->eq('val', 'foo'));

        $this
            ->persister
            ->expects($this->never())
            ->method('loadCriteria');

        $filtered1 = $this->lazyCriteriaCollection->matching($criteria);
        $filtered2 = $filtered1->matching($criteria);

        $this->assertFalse($filtered1->isInitialized());
        $this->assertFalse($filtered2->isInitialized());
        $this->assertNotSame($filtered1, $filtered2);
    }

    public function testSetsFirstResultFromProvidedCriteria()
    {
        $criteria = $this->getMock('Doctrine\Common\Collections\Criteria');

        $criteria
            ->expects($this->exactly(2))
            ->method('getFirstResult')
            ->willReturn(1);

        $criteria
            ->expects($this->any())
            ->method('setFirstResult')
            ->with(1);

        $criteria
            ->expects($this->once())
            ->method('getOrderings')
            ->willReturn([]);

        $criteria
            ->expects($this->any())
            ->method('orderBy')
            ->with([]);

        $this
            ->persister
            ->expects($this->never())
            ->method('loadCriteria');

        $this->lazyCriteriaCollection->matching($criteria);
    }

    public function testSetsMaxResultsFromProvidedCriteria()
    {
        $criteria = $this->getMock('Doctrine\Common\Collections\Criteria');

        $criteria
            ->expects($this->exactly(2))
            ->method('getMaxResults')
            ->willReturn(1);

        $criteria
            ->expects($this->any())
            ->method('setMaxResults')
            ->with(1);

        $criteria
            ->expects($this->once())
            ->method('getOrderings')
            ->willReturn([]);

        $criteria
            ->expects($this->any())
            ->method('orderBy')
            ->with([]);

        $this
            ->persister
            ->expects($this->never())
            ->method('loadCriteria');

        $this->lazyCriteriaCollection->matching($criteria);
    }
}
