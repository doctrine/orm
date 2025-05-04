<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsAddressDTO
{
    public function __construct(public string|null $country = null, public string|null $city = null, public string|null $zip = null, public string|null $address = null, public CmsDumbDTO|null $other = null)
    {
    }
}
