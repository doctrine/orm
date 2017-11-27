<?php

namespace Doctrine\Tests\Models\DDC1879;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class DDC1879Parent
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var DDC1879Child[]|ArrayCollection
     * @OneToMany(
     *     targetEntity="DDC1879Child",
     *     mappedBy="parent",
     *     cascade={"all"},
     *     orphanRemoval=true,
     *     fetch="EAGER"
     * )
     */
    public $children;

    /**
     * DDC1879Parent constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}
