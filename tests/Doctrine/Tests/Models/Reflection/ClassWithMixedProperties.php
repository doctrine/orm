<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Reflection;

class ClassWithMixedProperties extends ParentClass
{
    public static $staticProperty = 'staticProperty';

    public $publicProperty = 'publicProperty';

    protected $protectedProperty = 'protectedProperty';

    private $privateProperty = 'privateProperty';

    private $privatePropertyOverride = 'privatePropertyOverride';
}
