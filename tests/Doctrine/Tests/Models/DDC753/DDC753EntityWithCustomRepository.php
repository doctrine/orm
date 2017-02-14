<?php

namespace Doctrine\Tests\Models\DDC753;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity(repositoryClass = "Doctrine\Tests\Models\DDC753\DDC753CustomRepository")
 */
class DDC753EntityWithCustomRepository
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
