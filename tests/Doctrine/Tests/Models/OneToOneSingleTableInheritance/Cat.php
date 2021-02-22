<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneSingleTableInheritance;

/** @Entity */
class Cat extends Pet
{
    /**
     * @var LitterBox
     * @OneToOne(targetEntity="LitterBox")
     */
    public $litterBox;
}
