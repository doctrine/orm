<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\JoinedInheritanceType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class AnotherChildClass extends ChildClass
{
}
