<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsAddressDTO
{
    public function __construct(public ?string $country = null, public ?string $city = null, public ?string $zip = null)
    {
    }
}
