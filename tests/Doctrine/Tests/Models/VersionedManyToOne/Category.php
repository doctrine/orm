<?php

namespace Doctrine\Tests\Models\VersionedManyToOne;

/**
 * @Entity
 * @Table(name="versioned_many_to_one_category")
 */
class Category
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * Version column
     *
     * @Column(type="integer", name="version")
     * @Version
     */
    public $version;
}
