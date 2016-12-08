<?php

namespace Doctrine\Tests\Models\DDC3699;

/** @MappedSuperclass */
abstract class DDC3699Parent
{
    /** @Column(type="string") */
    public $parentField;
}
