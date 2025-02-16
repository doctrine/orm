<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Customer;

final class InternalCustomer extends CustomerType
{
    public function __construct(string $name)
    {
        parent::__construct($name);
    }
}
