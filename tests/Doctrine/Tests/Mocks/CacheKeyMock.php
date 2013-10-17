<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheKey;

class CacheKeyMock extends CacheKey
{
    function __construct($hash)
    {
        $this->hash = $hash;
    }
}
