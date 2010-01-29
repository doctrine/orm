<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\XcacheCache;

require_once __DIR__ . '/../../TestInit.php';

class XcacheCacheTest extends CacheTest
{
    public function setUp()
    {
        if ( ! extension_loaded('xcache')) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of xcache');
        }
    }

    protected function _getCacheDriver()
    {
        return new XcacheCache();
    }
}