<?php

namespace Doctrine\Tests\ORM;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\LazyCriteriaCollection;
use Doctrine\Tests\Mocks\ConnectionMock;
use PHPUnit_Framework_TestCase;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 *
 * @covers \Doctrine\ORM\LazyCriteriaCollection
 */
class LazyCriteriaCollectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\Persisters\EntityPersister|\PHPUnit_Framework_MockObject_MockObject
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
        $this->persister              = $this->getMock('Doctrine\ORM\Persisters\EntityPersister');
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
}
