<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsUserDTOVariadicArg
{
    public string|null $name      = null;
    public string|null $email     = null;
    public string|null $address   = null;
    public int|null $phonenumbers = null;

    public function __construct(...$args)
    {
        $this->name         = $args['name'] ?? null;
        $this->email        = $args['email'] ?? null;
        $this->phonenumbers = $args['phonenumbers'] ?? null;
        $this->address      = $args['address'] ?? null;
    }
}
