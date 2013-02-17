<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Access\ReadOnlyRegionAccess;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 */
class ReadOnlyRegionAccessTest extends NonStrictReadWriteRegionAccessTest
{
    protected function createRegionAccess()
    {
        return new ReadOnlyRegionAccess($this->region);
    }

    /**
     * @expectedException \Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Can't update a readonly object
     */
    public function testAfterUpdate()
    {
        $this->regionAccess->afterUpdate(new DefaultRegionTestKey('key'), array('value' => 'foo'));
    }
}
