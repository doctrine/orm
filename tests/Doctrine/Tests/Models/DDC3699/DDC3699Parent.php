<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3699;

/** @MappedSuperclass */
abstract class DDC3699Parent
{
    /**
     * @var string
     * @Column(type="string")
     */
    public $parentField;
}
