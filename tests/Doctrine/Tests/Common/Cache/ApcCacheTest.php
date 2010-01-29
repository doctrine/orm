<?php

namespace Doctrine\Tests\Common\Cache;

use Doctrine\Common\Cache\ApcCache;

require_once __DIR__ . '/../../TestInit.php';

class ApcCacheTest extends CacheTest
{
    public function setUp()
    {
        if ( ! extension_loaded('apc')) {
            $this->markTestSkipped('The ' . __CLASS__ .' requires the use of APC');
        }
    }

    protected function _getCacheDriver()
    {
        return new ApcCache();
    }
}