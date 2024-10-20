<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsUserDTO
{
    public function __construct(public string|null $name = null, public string|null $email = null, public CmsAddressDTO|string|null $address = null, public int|null $phonenumbers = null)
    {
    }
}
