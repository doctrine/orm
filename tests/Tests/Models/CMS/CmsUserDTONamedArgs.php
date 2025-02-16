<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsUserDTONamedArgs
{
    public function __construct(
        public string|null $name = null,
        public string|null $email = null,
        public string|null $address = null,
        public int|null $phonenumbers = null,
        public CmsAddressDTO|null $addressDto = null,
        public CmsAddressDTONamedArgs|null $addressDtoNamedArgs = null,
    ) {
    }
}
