<?php

namespace Doctrine\Tests\Models\DDC2042;

/**
 * Trait class
 */
trait DDC2042ExampleTrait
{
    /** @Id @Column(type="string") */
    private $id;

    /**
     * @Column(name="trait_foo", type="integer", length=100, nullable=true, unique=true)
     */
    protected $foo;

    /**
     * @OneToOne(targetEntity="DDC2042Bar", cascade={"persist", "merge"})
     * @JoinColumn(name="example_trait_bar_id", referencedColumnName="id")
     */
    protected $bar;
}
