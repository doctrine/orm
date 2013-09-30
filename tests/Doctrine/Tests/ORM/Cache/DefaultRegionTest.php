<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\Tests\Mocks\CacheEntryMock;
use Doctrine\Tests\Mocks\CacheKeyMock;

/**
 * @group DDC-2183
 */
class DefaultRegionTest extends AbstractRegionTest
{
    protected function createRegion()
    {
        return new DefaultRegion('default.region.test', $this->cache);
    }

    public function testGetters()
    {
        $this->assertEquals('default.region.test', $this->region->getName());
        $this->assertSame($this->cache, $this->region->getCache());
    }

    public function testSharedRegion()
    {
        if ( ! extension_loaded('apc') || false === @apc_cache_info()) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of APC');
        }

        $key     = new CacheKeyMock('key');
        $entry   = new CacheEntryMock(array('value' => 'foo'));
        $region1 = new DefaultRegion('region1', new \Doctrine\Common\Cache\ApcCache());
        $region2 = new DefaultRegion('region2', new \Doctrine\Common\Cache\ApcCache());

        $this->assertFalse($region1->contains($key));
        $this->assertFalse($region2->contains($key));

        $region1->put($key, $entry);
        $region2->put($key, $entry);

        $this->assertTrue($region1->contains($key));
        $this->assertTrue($region2->contains($key));

        $region1->evictAll();

        $this->assertFalse($region1->contains($key));
        $this->assertTrue($region2->contains($key));
    }
}