<?php

namespace Doctrine\Tests\Models\Mapping;

/**
 * Class Embedded
 * @package Doctrine\Tests\Models\Mapping
 */
class Embedded extends AbstractEmbedded
{
    /**
     * @var mixed
     */
    protected $bar;

    /**
     * @return mixed
     */
    public function getBar()
    {
        return $this->bar;
    }

    /**
     * @param $bar
     * @return $this
     */
    public function setBar($bar)
    {
        $this->bar = $bar;
        return $this;
    }
}
