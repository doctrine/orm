<?php

namespace Doctrine\Tests\Models\VersionedOneToOne;

use Doctrine\ORM\Annotation as ORM;

/**
 * @author Rob Caiger <rob@clocal.co.uk>
 *
 * @ORM\Entity
 * @ORM\Table(name="first_entity")
 */
class FirstRelatedEntity
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="SecondRelatedEntity", fetch="EAGER")
     * @ORM\JoinColumn(name="second_entity_id", referencedColumnName="id")
     */
    public $secondEntity;

    /**
     * @ORM\Column(name="name")
     */
    public $name;

    /**
     * Version column
     *
     * @ORM\Column(type="integer", name="version")
     * @ORM\Version
     */
    public $version;
}
