<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC4006;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC4006User
{
    /** @ORM\Embedded(class=DDC4006UserId::class) */
    private $id;
}
