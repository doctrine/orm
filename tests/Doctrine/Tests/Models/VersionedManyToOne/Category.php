<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\VersionedManyToOne;

/**
 * @Entity
 * @Table(name="versioned_many_to_one_category")
 */
class Category
{
    /**
     * @var int
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * Version column
     *
     * @var int
     * @Column(type="integer", name="version")
     * @Version
     */
    public $version;
}
