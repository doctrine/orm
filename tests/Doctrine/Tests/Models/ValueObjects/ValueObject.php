<?php

namespace Doctrine\Tests\Models\ValueObjects;

/**
 * @Embeddable
 */
class ValueObject
{
    /**
     * @Column(type="string")
     */
    public $value;

    /**
     * @Column(type="integer")
     */
    public $count;
}
