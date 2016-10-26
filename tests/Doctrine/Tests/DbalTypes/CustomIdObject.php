<?php

namespace Doctrine\Tests\DbalTypes;

class CustomIdObject
{
    /**
     * @var string
     */
    public $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = (string) $id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }
}
