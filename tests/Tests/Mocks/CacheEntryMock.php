<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use ArrayObject;
use Doctrine\ORM\Cache\CacheEntry;

/**
 * Cache entry mock
 */
class CacheEntryMock extends ArrayObject implements CacheEntry
{
}
