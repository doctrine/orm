<?php

namespace Doctrine\Tests\Models\VersionedOneToOne;

use Doctrine\ORM\Annotation as ORM;

/**
 * @author Rob Caiger <rob@clocal.co.uk>
 *
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
