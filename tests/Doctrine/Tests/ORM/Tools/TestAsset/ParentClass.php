<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\TestAsset;

abstract class ParentClass
{
    /** @var int */
    public $parentPublicAttribute = 1;
    /** @var int */
    protected $parentProtectedAttribute = 2;
    /** @var int */
    private $parentPrivateAttribute = 3;
}
