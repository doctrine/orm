<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Access\ReadOnlyCollectionRegionAccessStrategy;

/**
 * @group DDC-2183
 */
class ReadOnlyCollectionRegionAccessStrategyTest extends AbstractRegionAccessTest
{
    protected function createRegionAccess(Region $region)
    {
        return new ReadOnlyCollectionRegionAccessStrategy($region);
    }
}
