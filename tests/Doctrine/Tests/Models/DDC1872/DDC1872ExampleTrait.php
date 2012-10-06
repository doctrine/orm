<?php

namespace Doctrine\Tests\Models\DDC1872;

/**
 * Trait class
 */
trait DDC1872ExampleTrait
{
    /** @Id @Column(type="string") */
    private $id;

    /**
     * @Column(name="trait_foo", type="integer", length=100, nullable=true, unique=true)
     */
    protected $foo;

    /**
     * @OneToOne(targetEntity="DDC1872Bar", cascade={"persist", "merge"})
     * @JoinColumn(name="example_trait_bar_id", referencedColumnName="id")
     */
    protected $bar;
}
