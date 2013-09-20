<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Access\NonStrictReadWriteCollectionRegionAccessStrategy;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteCollectionRegionAccessStrategyTest extends AbstractRegionAccessTest
{
    protected function createRegionAccess(Region $region)
    {
        return new NonStrictReadWriteCollectionRegionAccessStrategy($region);
    }
}