<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Customer;

class CustomerType
{
    /** @var string */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
