<?php

namespace Doctrine\Tests\Models\VersionedManyToOne;

/**
 * @Entity
 * @Table(name="versioned_many_to_one_article")
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
     * @ManyToOne(targetEntity="Category", cascade={"merge", "persist"})
     */
    public $category;

    /**
     * Version column
     *
     * @Column(type="integer", name="version")
     * @Version
     */
    public $version;
}
