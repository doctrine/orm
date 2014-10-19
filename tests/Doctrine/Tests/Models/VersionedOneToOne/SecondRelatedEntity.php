<?php

namespace Doctrine\Tests\Models\VersionedOneToOne;

/**
 * @author Rob Caiger <rob@clocal.co.uk>
 *
 * @Entity
 * @Table(name="second_entity")
 */
class SecondRelatedEntity
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
