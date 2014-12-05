<?php

namespace Doctrine\Tests\Models\Reflection;

use ArrayObject;

/**
 * A test asset extending {@see \ArrayObject}, useful for verifying internal classes issues with reflection
 */
class ArrayObjectExtendingClass extends ArrayObject
{
    private $privateProperty;
    protected $protectedProperty;
    public $publicProperty;
}
