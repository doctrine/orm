<?php

namespace Doctrine\Tests\Models\JoinedInheritanceType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\MappedSuperClass
 */
abstract class ChildClass extends RootClass
{
}