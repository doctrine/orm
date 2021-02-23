<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\VersionedOneToOne;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="second_entity")
 */
class SecondRelatedEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

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
