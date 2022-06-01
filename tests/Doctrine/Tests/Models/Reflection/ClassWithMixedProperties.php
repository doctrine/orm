<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Reflection;

class ClassWithMixedProperties extends ParentClass
{
    /** @var string */
    public static $staticProperty = 'staticProperty';

    /** @var string */
    public $publicProperty = 'publicProperty';

    /** @var string */
    protected $protectedProperty = 'protectedProperty';

    private string $privateProperty = 'privateProperty';

    private string $privatePropertyOverride = 'privatePropertyOverride';
}
