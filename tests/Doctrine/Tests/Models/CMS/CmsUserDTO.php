<?php

namespace Doctrine\Tests\Models\CMS;

class CmsUserDTO
{
    public $name;
    public $email;
    public $address;

    public function __construct($name = null, $email = null, $address = null)
    {
        $this->name     = $name;
        $this->email    = $email;
        $this->address  = $address;
    }
}