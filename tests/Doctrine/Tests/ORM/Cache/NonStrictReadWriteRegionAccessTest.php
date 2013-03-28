<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Access\NonStrictReadWriteRegionAccessStrategy;

/**
 * @group DDC-2183
 */
class NonStrictReadWriteRegionAccessTest extends AbstractRegionAccessTest
{
    protected function createRegionAccess(Region $region)
    {
        return new NonStrictReadWriteRegionAccessStrategy($region);
    }
}