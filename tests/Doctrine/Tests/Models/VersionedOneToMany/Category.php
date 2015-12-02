<?php

namespace Doctrine\Tests\Models\VersionedOneToMany;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="category")
 */
class Category
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="Article", mappedBy="category", cascade={"merge", "persist"})
     */
    public $articles;

    /**
     * @Column(name="name")
     */
    public $name;

    /**
     * Version column
     *
     * @Column(type="integer", name="version")
     * @Version
     */
    public $version;

    /**
     * Category constructor.
     */
    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }
}
