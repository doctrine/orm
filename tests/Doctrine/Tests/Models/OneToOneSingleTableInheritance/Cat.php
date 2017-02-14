<?php

namespace Doctrine\Tests\Models\OneToOneSingleTableInheritance;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class Cat extends Pet
{
    /**
     * @ORM\OneToOne(targetEntity="LitterBox")
     *
     * @var LitterBox
     */
    public $litterBox;
}
