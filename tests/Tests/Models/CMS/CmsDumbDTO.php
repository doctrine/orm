<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsDumbDTO
{
    public function __construct(
        public mixed $val1 = null,
        public mixed $val2 = null,
        public mixed $val3 = null,
        public mixed $val4 = null,
        public mixed $val5 = null,
    ) {
    }
}
