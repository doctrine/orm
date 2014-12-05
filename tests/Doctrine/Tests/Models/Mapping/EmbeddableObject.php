<?php

namespace Doctrine\Tests\Models\Mapping;

/**
 * @Embeddable 
 */
class EmbeddableObject
{
    /**
     * @Column(type = "string") 
     */
    protected $foo;

    /**
     * @Column(type = "string") 
     */
    protected $bar;
}
