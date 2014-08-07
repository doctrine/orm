<?php

namespace Doctrine\Tests\Models\VersionedOneToOne;

/**
 * @author Rob Caiger <rob@clocal.co.uk>
 *
 * @Entity
 * @Table(name="first_entity")
 */
class FirstRelatedEntity
{
    /**
     * @Id
     * @OneToOne(targetEntity="SecondRelatedEntity", fetch="EAGER")
     * @JoinColumn(name="second_entity_id", referencedColumnName="id")
     */
    public $secondEntity;

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
