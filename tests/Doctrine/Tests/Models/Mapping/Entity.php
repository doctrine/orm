<?php

namespace Doctrine\Tests\Models\Mapping;

/**
 * Class Entity
 * @package Doctrine\Tests\Models\Mapping
 */
class Entity
{
    /**
     * @var \Doctrine\Tests\Models\Mapping\Embedded
     */
    protected $embedded;

    /**
     * @return Embedded
     */
    public function getEmbedded()
    {
        return $this->embedded;
    }

    /**
     * @param Embedded $embedded
     * @return $this
     */
    public function setEmbedded($embedded)
    {
        $this->embedded = $embedded;
        return $this;
    }
}
