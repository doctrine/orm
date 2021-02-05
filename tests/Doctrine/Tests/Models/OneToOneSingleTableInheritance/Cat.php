<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\OneToOneSingleTableInheritance;

/** @Entity */
class Cat extends Pet
{
    /**
     * @OneToOne(targetEntity="LitterBox")
     * @var LitterBox
     */
    public $litterBox;
}
