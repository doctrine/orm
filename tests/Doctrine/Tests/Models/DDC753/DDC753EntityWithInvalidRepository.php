<?php

namespace Doctrine\Tests\Models\DDC753;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity(repositoryClass = "\stdClass")
 */
class DDC753EntityWithInvalidRepository
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="string") */
    protected $name;

}
