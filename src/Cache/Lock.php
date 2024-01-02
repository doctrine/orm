<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use function time;
use function uniqid;

class Lock
{
    public int $time;

    public function __construct(
        public string $value,
        int|null $time = null,
    ) {
        $this->time = $time ?? time();
    }

    public static function createLockRead(): Lock
    {
        return new self(uniqid((string) time(), true));
    }
}
