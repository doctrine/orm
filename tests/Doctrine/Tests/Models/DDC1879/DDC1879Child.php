<?php

namespace Doctrine\Tests\Models\DDC1879;

/**
 * @Entity
 */
class DDC1879Child
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $value;

    /**
     * @var DDC1879Parent
     * @ManyToOne(targetEntity="DDC1879Parent", inversedBy="children", cascade={"all"})
     * @JoinColumn(name="parent", referencedColumnName="id", nullable=false)
     */
    public $parent;
}
