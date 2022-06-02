<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsUserDTO
{
    public function __construct(public ?string $name = null, public ?string $email = null, public ?string $address = null, public ?int $phonenumbers = null)
    {
    }
}
