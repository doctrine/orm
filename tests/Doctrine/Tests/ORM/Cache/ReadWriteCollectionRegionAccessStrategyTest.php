<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\Common\Cache\Cache;
use Doctrine\Tests\Mocks\ConcurrentRegionMock;
use Doctrine\ORM\Cache\Access\ReadWriteCollectionRegionAccessStrategy;

/**
 * @group DDC-2183
 */
class ReadWriteCollectionRegionAccessStrategyTest extends AbstractRegionAccessTest
{
    /**
     * @var Doctrine\Tests\Mocks\ConcurrentRegionMock
     */
    protected $regionAccess;

    protected function createRegionAccess(Region $region)
    {
        return new ReadWriteCollectionRegionAccessStrategy($region);
    }

    protected function createRegion(Cache $cache)
    {
        return new ConcurrentRegionMock(parent::createRegion($cache));
    }
}