<?php

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\ORM\Cache\Region;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Cache\EntityCacheKey;
use Doctrine\ORM\Cache\Region\DefaultRegion;
use Doctrine\ORM\Cache\Access\ReadOnlyRegionAccess;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @group DDC-2183
 */
class ReadOnlyRegionAccessTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var \Doctrine\ORM\Cache\Region
     */
    private $region;

    /**
     * @var \Doctrine\ORM\Cache\Access\ReadOnlyRegionAccess
     */
    private $regionAccess;

    protected function setUp()
    {
        parent::setUp();

        $this->region       = new DefaultRegion('DoctrineTestsModelsCacheCountry', new ArrayCache());
        $this->regionAccess = $this->createRegionAccess();
    }

    protected function createRegionAccess(Region $region = null)
    {
        return new ReadOnlyRegionAccess($region ?: $this->region);
    }

    public function testGetRegion()
    {
        $this->assertInstanceOf('Doctrine\ORM\Cache\Region', $this->regionAccess->getRegion());
    }

    static public function dataPutAndGet()
    {
        $entityName = '\Doctrine\Tests\Models\Cache\Country';
        
        return array(
            array(new EntityCacheKey(array('id'=>1), $entityName), array('id'=>1, 'name' => 'bar')),
            array(new EntityCacheKey(array('id'=>2), $entityName), array('id'=>2, 'name' => 'foo')),
        );
    }

    /**
     * @dataProvider dataPutAndGet
     */
    public function testPutAndGet($key, $value)
    {
        $this->regionAccess->put($key, $value);

        $actual = $this->regionAccess->get($key);

        $this->assertEquals($value, $actual);
    }

}
