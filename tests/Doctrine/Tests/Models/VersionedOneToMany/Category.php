<?php

namespace Doctrine\Tests\Models\VersionedOneToMany;

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
}
