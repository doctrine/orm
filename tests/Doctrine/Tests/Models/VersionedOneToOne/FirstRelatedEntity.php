<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\VersionedOneToOne;

/**
 * @Entity
 * @Table(name="first_entity")
 */
class FirstRelatedEntity
{
    /**
     * @var SecondRelatedEntity
     * @Id
     * @OneToOne(targetEntity="SecondRelatedEntity", fetch="EAGER")
     * @JoinColumn(name="second_entity_id", referencedColumnName="id")
     */
    public $secondEntity;

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
