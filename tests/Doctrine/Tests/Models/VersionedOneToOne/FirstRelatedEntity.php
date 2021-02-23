<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\VersionedOneToOne;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="first_entity")
 */
class FirstRelatedEntity
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity=SecondRelatedEntity::class, fetch="EAGER")
     * @ORM\JoinColumn(name="second_entity_id", referencedColumnName="id")
     */
    public $secondEntity;

    /** @ORM\Column(name="name") */
    public $name;

    /**
     * Version column
     *
     * @ORM\Column(type="integer", name="version")
     * @ORM\Version
     */
    public $version;
}
