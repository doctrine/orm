<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

class CmsUserDTO
{
    /** @var string|null */
    public $name;

    /** @var string|null */
    public $email;

    /** @var string|null */
    public $address;

    /** @var int|null */
    public $phonenumbers;

    public function __construct(
        ?string $name = null,
        ?string $email = null,
        ?string $address = null,
        ?int $phonenumbers = null
    ) {
        $this->name         = $name;
        $this->email        = $email;
        $this->address      = $address;
        $this->phonenumbers = $phonenumbers;
    }
}
