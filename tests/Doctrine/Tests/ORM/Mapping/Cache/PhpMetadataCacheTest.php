<?php

namespace Doctrine\Tests\ORM\Mapping\Cache;

use Doctrine\Common\Cache\Psr6\CacheItem;
use Doctrine\Common\Cache\Psr6\TypedCacheItem;
use Doctrine\ORM\Mapping\Cache\PhpMetadataCache;
use Doctrine\Tests\OrmFunctionalTestCase;

class PhpMetadataCacheTest extends OrmFunctionalTestCase
{
    /**
     * @test
     */
    public function testLoadedMetadataEqualUncached()
    {
        $cache = new PhpMetadataCache(sys_get_temp_dir() . '/dcmetatest');
        $itemClass = (PHP_VERSION_ID >= 80000)
            ? TypedCacheItem::class
            : CacheItem::class;

        foreach (self::$modelSets as $classes) {
            foreach ($classes as $class) {
                $metadata = $this->_em->getMetadataFactory()->getMetadataFor($class);

                $key = str_replace("\\", ".", $metadata->name);
                $cache->save(new $itemClass($key, $metadata, false));

                $cached = $cache->getItem($key)->get();
                $cached->reflClass = $metadata->reflClass;
                $cached->reflFields = $metadata->reflFields;

                $this->assertEquals($metadata, $cached);
            }
        }
    }

    /**
     * @after
     */
    public function removeMetadataTestDirectory()
    {
        $dir = sys_get_temp_dir() . '/dcmetatest';

        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === "." || $file === "..") {
                    continue;
                }

                unlink($dir . "/" . $file);
            }
            rmdir($dir);
        }
    }
}
