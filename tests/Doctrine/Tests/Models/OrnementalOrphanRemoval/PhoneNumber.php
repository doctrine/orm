<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OrnementalOrphanRemoval;

class PhoneNumber
{
    /** @var string */
    public $id;

    /** @var Person */
    public $person;

    /** @var string */
    public $phonenumber;
}
