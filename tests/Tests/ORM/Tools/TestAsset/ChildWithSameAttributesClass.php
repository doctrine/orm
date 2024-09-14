<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\TestAsset;

final class ChildWithSameAttributesClass extends ParentClass
{
    /** @var int */
    public $parentPublicAttribute = 4;
    /** @var int */
    protected $parentProtectedAttribute = 5;
    /** @var int */
    private $parentPrivateAttribute = 6;
}
