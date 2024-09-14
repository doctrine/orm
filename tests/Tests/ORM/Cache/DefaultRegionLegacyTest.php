<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Cache;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\Region\DefaultRegion;

class DefaultRegionLegacyTest extends DefaultRegionTest
{
    use VerifyDeprecations;

    protected function createRegion(): Region
    {
        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/9322');

        return new DefaultRegion('default.region.test', DoctrineProvider::wrap($this->cacheItemPool));
    }
}
