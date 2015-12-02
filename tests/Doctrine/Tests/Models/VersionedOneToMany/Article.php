<?php

namespace Doctrine\Tests\Models\VersionedOneToMany;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="article")
 */
class Article
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(name="name")
     */
    public $name;

    /**
     * @ManyToOne(targetEntity="Category", inversedBy="category", cascade={"merge", "persist"})
     */
    public $category;

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
        $this->tags = new ArrayCollection();
    }
}
