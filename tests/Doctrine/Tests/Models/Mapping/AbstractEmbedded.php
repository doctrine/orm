<?php

namespace Doctrine\Tests\Models\Mapping;

/**
 * Class AbstractEmbedded
 * @package Doctrine\Tests\Models\Mapping
 */
abstract class AbstractEmbedded
{
    /**
     * @var
     */
    protected $foo;

    /**
     * @return mixed
     */
    public function getFoo()
    {
        return $this->foo;
    }

    /**
     * @param $foo
     * @return $this
     */
    public function setFoo($foo)
    {
        $this->foo = $foo;
        return $this;
    }
}
