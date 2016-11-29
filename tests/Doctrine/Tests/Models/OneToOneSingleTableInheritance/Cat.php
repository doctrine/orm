<?php

namespace Doctrine\Tests\Models\OneToOneSingleTableInheritance;

/** @Entity */
class Cat extends Pet
{
    const CLASSNAME = __CLASS__;

    /**
     * @OneToOne(targetEntity="LitterBox")
     *
     * @var LitterBox
     */
    public $litterBox;
}
