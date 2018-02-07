<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneSingleTableInheritance;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class Cat extends Pet
{
    /**
     * @ORM\OneToOne(targetEntity=LitterBox::class)
     *
     * @var LitterBox
     */
    public $litterBox;
}
