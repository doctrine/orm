<?php

namespace Doctrine\Tests\Models\CMS;

class CmsUserDTO
{
    public $name;
    public $email;
    public $city;

    public function __construct($name = null, $email = null, $city = null)
    {
        $this->name  = $name;
        $this->email = $email;
        $this->city  = $city;
    }
}