<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\VersionedOneToOne;

/**
 * @Entity
 * @Table(name="second_entity")
 */
class SecondRelatedEntity
{
    /**
     * @var int
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(name="name")
     */
    public $name;

    /**
     * @var int
     * Version column
     * @Column(type="integer", name="version")
     * @Version
     */
    public $version;
}
