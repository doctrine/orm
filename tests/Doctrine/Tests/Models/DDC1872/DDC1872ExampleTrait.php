<?php

namespace Doctrine\Tests\Models\DDC1872;

use Doctrine\ORM\Annotation as ORM;

/**
 * Trait class
 */
trait DDC1872ExampleTrait
{
    /** @ORM\Id @ORM\Column(type="string") */
    private $id;

    /**
     * @ORM\Column(name="trait_foo", type="integer", length=100, nullable=true, unique=true)
     */
    protected $foo;

    /**
     * @ORM\OneToOne(targetEntity="DDC1872Bar", cascade={"persist", "merge"})
     * @ORM\JoinColumn(name="example_trait_bar_id", referencedColumnName="id")
     */
    protected $bar;
}
