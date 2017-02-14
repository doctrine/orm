<?php

namespace Doctrine\Tests\Models\JoinedInheritanceType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 */
class RootClass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue
     */
    public $id;
}