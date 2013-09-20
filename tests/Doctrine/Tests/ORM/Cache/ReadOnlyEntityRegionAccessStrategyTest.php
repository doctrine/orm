<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\Tests\Mocks\CacheKeyMock;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\ORM\Cache\Access\ReadOnlyEntityRegionAccessStrategy;

/**
 * @group DDC-2183
 */
class ReadOnlyEntityRegionAccessStrategyTest extends AbstractEntityRegionAccessTest
{
    protected function createRegionAccess(Region $region)
    {
        return new ReadOnlyEntityRegionAccessStrategy($region);
    }

    /**
     * @expectedException \Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Can't update a readonly object
     */
    public function testAfterUpdate()
    {
        $this->regionAccess->afterUpdate(new CacheKeyMock('key'), new CacheEntryMock(array('value' => 'foo')));
    }
}
