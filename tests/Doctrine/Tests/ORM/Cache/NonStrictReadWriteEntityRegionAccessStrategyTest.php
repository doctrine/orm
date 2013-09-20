<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Access\NonStrictReadWriteEntityRegionAccessStrategy;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteEntityRegionAccessStrategyTest extends AbstractEntityRegionAccessTest
{
    protected function createRegionAccess(Region $region)
    {
        return new NonStrictReadWriteEntityRegionAccessStrategy($region);
    }
}