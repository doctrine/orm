<?php

namespace Doctrine\Tests\Mocks;

use Doctrine\ORM\Cache\CacheKey;

class CacheKeyMock implements CacheKey
{

    function __construct($hash)
    {
        $this->hash = $hash;
    }

    public function hash()
    {
        return $this->hash;
    }
}