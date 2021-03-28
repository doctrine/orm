<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsAddressDTO
{
    /** @var string|null */
    public $country;

    /** @var string|null */
    public $city;

    /** @var string|null */
    public $zip;

    public function __construct(
        ?string $country = null,
        ?string $city = null,
        ?string $zip = null
    ) {
        $this->country = $country;
        $this->city    = $city;
        $this->zip     = $zip;
    }
}
